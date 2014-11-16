<?php namespace peer\http\unittest;

use peer\http\HttpRequest;
use peer\http\HttpResponse;
use peer\http\io\ToLog;

/**
 * Mock HTTP connection
 *
 * @see   xp://peer.http.HttpConnection
 */
class MockHttpConnection extends \peer\http\HttpConnection {
  protected $lastRequest= null;
  protected $cat= null;

  /** @return peer.http.HttpRequest */
  public function lastRequest() { return $this->lastRequest; }

  /**
   * Send a HTTP request
   *
   * @param   peer.http.HttpRequest
   * @return  peer.http.HttpResponse response object
   */
  public function send(HttpRequest $request) {
    $this->lastRequest= $request;

    $this->cat && $request->write(new ToLog($this->cat, '>>>'));
    $response= new HttpResponse(new \io\streams\MemoryInputStream("HTTP/1.0 200 Testing OK\r\n"));
    $this->cat && $this->cat->info('<<<', $response->getHeaderString());
    return $response;
  }

  /**
   * Sets a logger category for debugging
   *
   * @param   util.log.LogCategory $cat
   */
  public function setTrace($cat) {
    $this->cat= $cat;
  }
}
