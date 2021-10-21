<?php namespace peer\http;

use peer\URL;
use util\log\Traceable;

/**
 * HTTP connection
 *
 * ```
 * $c= new HttpConnection('https://example.com/');
 * $response= $c->get(['a' => 'b'], [
 *   new Header('X-Binford', '6100 (more power)'),
 *   new BasicAuthorization('baz', 'bar'),
 *   'Cookie' => 'username=fred; lastvisit=2004-01-10'
 * ]);
 * Console::writeLine('Headers: ', $response);
 *
 * while ($chunk= $response->readData()) {
 *   // ...
 * }
 * ```
 *
 * @see   rfc://2616
 * @test  xp://net.xp_framework.unittest.peer.HttpTest
 */
class HttpConnection implements Traceable {
  protected
    $url          = null,
    $transport    = null,
    $_ctimeout    = 2.0,
    $_timeout     = 60;

  /**
   * Constructor
   *
   * @param  string|peer.URL $url
   * @param  peer.http.HttpTransport $transport
   */
  public function __construct($url, $transport= null) {
    $this->url= $url instanceof URL ? $url : new URL($url);
    $this->transport= $transport ?? HttpTransport::transportFor($this->url);
  }

  /**
   * Set proxy
   *
   * @param   peer.http.HttpProxy $proxy
   */
  public function setProxy(HttpProxy $proxy= null) {
    $this->transport->setProxy($proxy);
  }

  /**
   * Set connect timeout
   *
   * @param   float $timeout
   */
  public function setConnectTimeout($timeout) {
    $this->_ctimeout= $timeout;
  }

  /**
   * Get timeout
   *
   * @return  float
   */
  public function getConnectTimeout() {
    return $this->_ctimeout;
  }

  /**
   * Set timeout
   *
   * @param   int $timeout
   */
  public function setTimeout($timeout) {
    $this->_timeout= $timeout;
  }

  /**
   * Get timeout
   *
   * @return  int
   */
  public function getTimeout() {
    return $this->_timeout;
  }

  /**
   * Get URL
   *
   * @return  peer.URL
   */
  public function getUrl() {
    return $this->url;
  }
  
  /**
   * Returns a string representation of this object
   *
   * @return  string
   */
  public function toString() {
    return sprintf(
      '%s(->URL{%s via %s}, timeout: [read= %.2f, connect= %.2f])',
      nameof($this),
      $this->url->getUrl(),
      $this->transport->toString(),
      $this->_timeout,
      $this->_ctimeout
    );
  }
  
  /**
   * Send a HTTP request
   *
   * @param   peer.http.HttpRequest $request
   * @return  peer.http.HttpResponse response object
   */
  public function send(HttpRequest $request) {
    return $this->transport->send($request, $this->_timeout, $this->_ctimeout);
  }

  /**
   * Opens a HTTP transfer
   *
   * ```php
   * $transfer= $this->conn->open($request);
   * $transfer->write(...);
   *
   * $response= $this->conn->finish($transfer);
   * ```
   *
   * @param   peer.http.HttpRequest $request
   * @return  peer.http.HttpOutputStream
   */
  public function open(HttpRequest $request) {
    return $this->transport->open($request, $this->_ctimeout, $this->_timeout);
  }

  /**
   * Finishes a transfer and returns the response
   *
   * @param  peer.http.HttpOutputStream $stream
   * @return peer.http.HttpResponse
   */
  public function finish(HttpOutputStream $stream) {
    return $this->transport->finish($stream);
  }

  /**
   * Creates a new HTTP request. For use in conjunction with send(), e.g.:
   *
   * ```php
   * $conn= new HttpConnection('http://example.com/');
   *   
   * $request= $conn->create(new HttpRequest());
   * $request->setMethod(HttpConstants::GET);
   * $request->setParameters(array('a' => 'b'));
   * $request->setHeader('X-Binford', '6100 (more power)');
   *
   * $response= $conn->send($request);
   * ```
   *
   * @param   peer.http.HttpRequest $r
   * @return  peer.http.HttpRequest request object
   */
  public function create(HttpRequest $r) {
    $r->setUrl(clone $this->url);
    return $r;
  }
  
  /**
   * Perform any request
   *
   * @param   string $method request method, e.g. HttpConstants::GET
   * @param   var $parameters
   * @param   [:string] $headers default array()
   * @return  peer.http.HttpResponse response object
   * @throws  io.IOException
   */
  public function request($method, $parameters= null, $headers= []) {
    $r= new HttpRequest($this->url);
    $r->setMethod($method);
    $r->setParameters($parameters);
    $r->addHeaders($headers);
    return $this->send($r);
  }

  /**
   * Perform a GET request
   *
   * @param   string $arg default NULL
   * @param   [:var] $headers default array()
   * @return  peer.http.HttpResponse response object
   */
  public function get($arg= null, $headers= []) {
    return $this->request(HttpConstants::GET, $arg, $headers);
  }
  
  /**
   * Perform a HEAD request
   *
   * @param   string $arg default NULL
   * @param   [:var] $headers default array()
   * @return  peer.http.HttpResponse response object
   */
  public function head($arg= null, $headers= []) {
    return $this->request(HttpConstants::HEAD, $arg, $headers);
  }
  
  /**
   * Perform a POST request
   *
   * @param   string $arg default NULL
   * @param   [:var] $headers default array()
   * @return  peer.http.HttpResponse response object
   */
  public function post($arg= null, $headers= []) {
    return $this->request(HttpConstants::POST, $arg, $headers);
  }
  
  /**
   * Perform a PUT request
   *
   * @param   string $arg default NULL
   * @param   [:var] $headers default array()
   * @return  peer.http.HttpResponse response object
   */
  public function put($arg= null, $headers= []) {
    return $this->request(HttpConstants::PUT, $arg, $headers);
  }

  /**
   * Perform a PATCH request
   *
   * @param   string $arg default NULL
   * @param   [:var] $headers default array()
   * @return  peer.http.HttpResponse response object
   */
  public function patch($arg= null, $headers= []) {
    return $this->request(HttpConstants::PATCH, $arg, $headers);
  }

  /**
   * Perform a DELETE request
   *
   * @param   string $arg default NULL
   * @param   [:var] $headers default array()
   * @return  peer.http.HttpResponse response object
   */
  public function delete($arg= null, $headers= []) {
    return $this->request(HttpConstants::DELETE, $arg, $headers);
  }

  /**
   * Perform an OPTIONS request
   *
   * @param   string $arg default NULL
   * @param   [:var] $headers default array()
   * @return  peer.http.HttpResponse response object
   */
  public function options($arg= null, $headers= []) {
    return $this->request(HttpConstants::OPTIONS, $arg, $headers);
  }

  /**
   * Perform a TRACE request
   *
   * @param   string $arg default NULL
   * @param   [:var] $headers default array()
   * @return  peer.http.HttpResponse response object
   */
  public function trace($arg= null, $headers= []) {
    return $this->request(HttpConstants::TRACE, $arg, $headers);
  }

  /**
   * Sets a logger category for debugging
   *
   * @param   util.log.LogCategory $cat
   */
  public function setTrace($cat) {
    $this->transport->setTrace($cat);
  }
}