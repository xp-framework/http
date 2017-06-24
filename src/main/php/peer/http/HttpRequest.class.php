<?php namespace peer\http;

use peer\URL;
use peer\http\BasicAuthorization;

/**
 * Wrap HTTP/1.0 and HTTP/1.1 requests (used internally by the HttpConnection
 * class)
 *
 * @test  xp://peer.http.unittest.HttpRequestTest
 * @see   xp://peer.http.HttpConnection
 * @see   rfc://2616
 */
class HttpRequest {
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
   * Set URL
   *
   * @param   peer.URL url object
   */
  public function setUrl(URL $url) {
    $this->url= $url;
    if ($url->getUser() && $url->getPassword()) {
      $this->setHeader('Authorization', new BasicAuthorization($url->getUser(), $url->getPassword()));
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
   * @param  var $arg either a string, a RequestData object or an associative array
   */
  public function setParameters($arg) {
    if ($arg instanceof RequestData) {
      $this->parameters= $arg;
      return;
    } else if (is_string($arg)) {
      if ('' === $arg) {
        $params= [];
      } else if ('/' === $arg{0}) {
        sscanf($arg, "%[^?]?%[^\r]", $path, $query);
        $this->setTarget($path);
        parse_str($query, $params);
      } else {
        parse_str($arg, $params);
      }
    } else if (is_array($arg)) {
      $params= $arg;
    } else {
      $params= [];
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
   * @param   string $name header name
   * @param   string|string[]|peer.http.Header|peer.http.Authorization $header header value
   */
  public function setHeader($name, $header) {
    if ($header instanceof Header) {
      $this->headers[$header->name()]= [$header->value()];
    } else if ($header instanceof Authorization) {      // BC
      $header->sign($this);
    } else if ($header instanceof \peer\Header) {       // BC
      $this->headers[$header->getName()]= [$header->getValueRepresentation()];
    } else {
      $this->headers[$name]= (array)$header;
    }
  }

  /**
   * Add headers
   *
   * @param   [:var] $headers
   */
  public function addHeaders($headers) {
    foreach ($headers as $name => $header) {
      $this->setHeader($name, $header);
    }
  }

  /**
   * Returns payload
   *
   * @param   bool withBody
   */
  protected function getPayload($withBody) {
    static $params= [
      HttpConstants::HEAD    => true,
      HttpConstants::GET     => true,
      HttpConstants::DELETE  => true,
      HttpConstants::OPTIONS => true
    ];

    if ($this->parameters instanceof RequestData) {
      $this->addHeaders($this->parameters->getHeaders());
      $query= '&'.$this->parameters->getData();
      $useParams= false;
    } else {
      $useParams= isset($params[$this->method]);
      $query= '';
      foreach ($this->parameters as $name => $value) {
        if (is_array($value)) {
          foreach ($value as $k => $v) {
            $query.= '&'.urlencode($name).'['.urlencode($k).']='.urlencode($v);
          }
        } else {
          $query.= '&'.urlencode($name).'='.urlencode($value);
        }
      }
    }
    $target= $this->target;
    $body= '';

    if ($useParams) {
      if (null !== $this->url->getQuery()) {
        $target.= '?'.$this->url->getQuery().(empty($query) ? '' : $query);
      } else {
        $target.= empty($query) ? '' : '?'.substr($query, 1);
      }
    } else {
      if ($withBody) $body= substr($query, 1);
      if (null !== $this->url->getQuery()) $target.= '?'.$this->url->getQuery();
      $this->headers['Content-Length']= [max(0, strlen($query)- 1)];
      if (empty($this->headers['Content-Type'])) {
        $this->headers['Content-Type']= ['application/x-www-form-urlencoded'];
      }
    }

    $request= sprintf("%s %s HTTP/%s\r\n", $this->method, $target, $this->version);
    foreach ($this->headers as $name => $values) {
      foreach ($values as $header) {
        $request.= $name.': '.$header."\r\n";
      }
    }

    return $request."\r\n".$body;
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
