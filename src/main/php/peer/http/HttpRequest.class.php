<?php namespace peer\http;

use peer\URL;
use peer\Header;
use io\streams\Seekable;
use io\streams\InputStream;
use io\streams\MemoryInputStream;
use peer\http\io\To;

/**
 * Wrap HTTP/1.0 and HTTP/1.1 requests (used internally by the HttpConnection
 * class)
 *
 * @test  xp://peer.http.unittest.HttpRequestTest
 * @see   xp://peer.http.HttpConnection
 * @see   rfc://2616
 */
class HttpRequest extends \lang\Object {
  private $in= null;

  public
    $url        = null,
    $method     = HttpConstants::GET,
    $target     = '',
    $version    = HttpConstants::VERSION_1_1,
    $headers    = ['Connection' => ['close']],
    $parameters = [];

  /**
   * Constructor
   *
   * @param   peer.URL url object
   */
  public function __construct(URL $url= null) {
    if (null !== $url) $this->setUrl($url);
  }

  /**
   * Sets body
   *
   * @param  var $in A Body instance, an InputStream, a string or an array
   */
  public function withBody($arg) {
    if ($arg instanceof Body) {
      $this->addHeaders($arg->headers());
      $this->in= $arg->stream();
    } else if ($arg instanceof InputStream) {
      $this->headers['Content-Type']= ['application/x-www-form-urlencoded'];
      if ($arg instanceof Seekable) {
        $pos= $arg->tell();
        $arg->seek(0, SEEK_END);
        $this->headers['Content-Length']= [$arg->tell()];
        $arg->seek($pos, SEEK_SET);
      } else {
        $this->headers['Content-Transfer-Encoding']= ['chunked'];
      }
      $this->in= $arg;
    } else if (null !== $arg) {
      $this->withBody(new RequestData($arg));
    }
  }

  /** @return io.streams.InputStream */
  public function in() { return $this->in; }

  /**
   * Set URL
   *
   * @param   peer.URL url object
   */
  public function setUrl(URL $url) {
    $this->url= $url;
    if ($url->getUser() && $url->getPassword()) {
      $this->headers['Authorization']= ['Basic '.base64_encode($url->getUser().':'.$url->getPassword())];
    }
    $port= $this->url->getPort(-1);
    $this->headers['Host']= [$this->url->getHost().(-1 == $port ? '' : ':'.$port)];
    $this->target= $this->url->getPath('/');
  }

  /**
   * Get URL
   *
   * @return  peer.URL url object
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Set request target
   *
   * @param   string target
   */
  public function setTarget($target) {
    $this->url->setPath($target);
    $this->target= $this->url->getPath('/');
  }
  
  /**
   * Set request method
   *
   * @param   string method request method, e.g. HttpConstants::GET
   */
  public function setMethod($method) {
    $this->method= $method;
  }

  /**
   * Set request parameters
   *
   * @param   var $arg either a string, a RequestData object or an associative array
   */
  public function setParameters($arg) {
    if ($arg instanceof Body) {      // BC
      $this->withBody($arg);
      return;
    } else if (is_string($arg)) {
      parse_str($arg, $out); 
      $this->parameters= $out;
    } else if (is_array($arg)) {
      $this->parameters= $arg;
    }
  }
  
  /**
   * Set a single request parameter
   *
   * @param   string name
   * @param   string value
   */
  public function setParameter($name, $value) {
    $this->parameters[$name]= $value;
  }
  
  /**
   * Set header
   *
   * @param   string k header name
   * @param   var v header value either a string, string[] or peer.Header
   */
  public function setHeader($k, $v) {
    if (is_array($v)) {
      $this->headers[$k]= $v;
    } else {
      $this->headers[$k]= [$v];
    }
  }

  /**
   * Add headers
   *
   * @param   [:var] headers
   */
  public function addHeaders($headers) {
    foreach ($headers as $key => $header) {
      $this->setHeader($header instanceof Header ? $header->getName() : $key, $header);
    }
  }

  /**
   * Writes this request
   *
   * @param  peer.http.io.To $out
   * @return peer.http.io.To The given $out
   */
  public function write(To $out) {
    $query= '';
    foreach (array_merge($this->url->getParams(), $this->parameters) as $name => $value) {
      if (is_array($value)) {
        foreach ($value as $k => $v) {
          $query.= '&'.$name.'['.$k.']='.urlencode($v);
        }
      } else {
        $query.= '&'.$name.'='.urlencode($value);
      }
    }
    $out->request($this->method, $this->target.(empty($query) ? '' : '?'.substr($query, 1)), $this->version);

    foreach ($this->headers as $name => $values) {
      foreach ($values as $arg) {
        if ($arg instanceof Header) {
          $out->header($arg->getName(), $arg->getValueRepresentation());
        } else {
          $out->header($name, $arg);
        }
      }
    }

    $out->commit();
    if (null !== $this->in) {
      $out->body($this->in);
    }
    return $out;
  }
}
