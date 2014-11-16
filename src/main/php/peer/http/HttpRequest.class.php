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
   * Use an input stream to upload from
   *
   * @param  var $in Either a string or an InputStream
   */
  public function useStream($in) {
    if ($in instanceof Seekable) {
      $this->in= $in;
      $pos= $this->in->tell();
      $this->in->seek(0, SEEK_END);
      $this->headers['Content-Length']= [$this->in->tell()];
      $this->in->seek($pos, SEEK_SET);
    } else if ($in instanceof InputStream) {
      $this->in= $in;
      $this->headers['Content-Transfer-Encoding']= ['chunked'];
    } else {
      $this->in= new MemoryInputStream($in);
      $this->headers['Content-Length']= [strlen($in)];
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
      $this->headers['Authorization']= array('Basic '.base64_encode($url->getUser().':'.$url->getPassword()));
    }
    $port= $this->url->getPort(-1);
    $this->headers['Host']= array($this->url->getHost().(-1 == $port ? '' : ':'.$port));
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
   * @param   var p either a string, a RequestData object or an associative array
   */
  public function setParameters($p) {
    if ($p instanceof RequestData) {
      $this->parameters= $p;
      return;
    } else if (is_string($p)) {
      parse_str($p, $out); 
      $params= $out;
    } else if (is_array($p)) {
      $params= $p;
    } else {
      $params= array();
    }
    
    $this->parameters= array_diff($params, $this->url->getParams());
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
   * Returns payload
   *
   * @param   bool withBody
   */
  protected function getPayload($withBody) {
   if ($this->parameters instanceof RequestData) {
      $this->addHeaders($this->parameters->getHeaders());
      $query= '&'.$this->parameters->getData();
    } else {
      $query= '';
      foreach ($this->parameters as $name => $value) {
        if (is_array($value)) {
          foreach ($value as $k => $v) {
            $query.= '&'.$name.'['.$k.']='.urlencode($v);
          }
        } else {
          $query.= '&'.$name.'='.urlencode($value);
        }
      }
    }
    $target= $this->target;
    $body= '';

    if (null === $this->in) {

      // Which HTTP method? GET and HEAD use query string, POST etc. use
      // body for passing parameters
      switch ($this->method) {
        case HttpConstants::HEAD: case HttpConstants::GET: case HttpConstants::DELETE: case HttpConstants::OPTIONS:
          if (null !== $this->url->getQuery()) {
            $target.= '?'.$this->url->getQuery().(empty($query) ? '' : $query);
          } else {
            $target.= empty($query) ? '' : '?'.substr($query, 1);
          }
          break;

        case HttpConstants::POST: case HttpConstants::PUT: case HttpConstants::TRACE: default:
          if ($withBody) $body= substr($query, 1);
          if (null !== $this->url->getQuery()) $target.= '?'.$this->url->getQuery();
          $this->headers['Content-Length']= array(max(0, strlen($query)- 1));
          if (empty($this->headers['Content-Type'])) {
            $this->headers['Content-Type']= ['application/x-www-form-urlencoded'];
          }
          break;
      }
    }

    $request= sprintf(
      "%s %s HTTP/%s\r\n",
      $this->method,
      $target,
      $this->version
    );

    // Add request headers
    foreach ($this->headers as $k => $v) {
      foreach ($v as $value) {
        $request.= ($value instanceof Header ? $value->toString() : $k.': '.$value)."\r\n";
      }
    }

    return $request."\r\n".$body;
  }

  /**
   * Writes this request
   *
   * @param  peer.http.io.To $out
   * @return peer.http.io.To The given $out
   */
  public function write(To $out) {
    $query= $this->url->getQuery('');
    foreach ($this->parameters as $name => $value) {
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

  /**
   * Returns HTTP request headers as being written to server
   *
   * @return  string
   */
  public function getHeaderString() {
    return $this->getPayload(false);
  }
  
  /**
   * Get request string
   *
   * @return  string
   */
  public function getRequestString() {
    return $this->getPayload(true);
  }
}
