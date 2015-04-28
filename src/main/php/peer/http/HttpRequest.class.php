<?php namespace peer\http;

use peer\URL;
use peer\Header;
use peer\http\BasicAuthorization;
use security\SecureString;

/**
 * Wrap HTTP/1.0 and HTTP/1.1 requests (used internally by the HttpConnection
 * class)
 *
 * @test  xp://peer.http.unittest.HttpRequestTest
 * @see   xp://peer.http.HttpConnection
 * @see   rfc://2616
 */
class HttpRequest extends \lang\Object {
  public
    $url        = null,
    $method     = HttpConstants::GET,
    $target     = '',
    $version    = HttpConstants::VERSION_1_1,
    $headers    = array('Connection' => array('close')),
    $parameters = array();
    
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
      $this->setHeader('Authorization', new BasicAuthorization($url->getUser(), new SecureString($url->getPassword())));
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

      // Handle special BC case when eg. BasicAuthorization instance being passed
      if ($v instanceof Authorization) {
        $v->sign($this);
      } else {
        $this->headers[$k]= array($v);
      }
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
    $payloadIntoBody= !in_array($this->method, [HttpConstants::HEAD, HttpConstants::GET, HttpConstants::DELETE, HttpConstants::OPTIONS]);

    if ($this->parameters instanceof RequestData) {

      // RequestData parameters will always be put into the HTTP request's body
      $payloadIntoBody= true;

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

    // Which HTTP method? GET and HEAD use query string, POST etc. use
    // body for passing parameters
    if ($payloadIntoBody) {
      if ($withBody) $body= substr($query, 1);
      if (null !== $this->url->getQuery()) $target.= '?'.$this->url->getQuery();
      $this->headers['Content-Length']= array(max(0, strlen($query)- 1));
      if (empty($this->headers['Content-Type'])) {
        $this->headers['Content-Type']= array('application/x-www-form-urlencoded');
      }
    } else {
      if (null !== $this->url->getQuery()) {
        $target.= '?'.$this->url->getQuery().(empty($query) ? '' : $query);
      } else {
        $target.= empty($query) ? '' : '?'.substr($query, 1);
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
