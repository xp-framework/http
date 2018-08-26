<?php namespace peer\http\unittest;

use io\streams\MemoryInputStream;
use peer\http\HttpConnection;
use peer\http\HttpRequest;
use peer\http\HttpResponse;

/**
 * Mock HTTP connection
 *
 * @see   xp://peer.http.HttpConnection
 */
class MockHttpConnection extends HttpConnection {
  private $lastRequest= null;
  private $response= null;
  private $cat= null;

  /** @return string */
  public function lastRequest() { return $this->lastRequest; }

  /** @param peer.http.HttpResponse $response */
  public function setResponse(HttpResponse $response) { $this->response= $response; }

  /** @return peer.http.HttpResponse */
  private function response() {
    if ($this->response) {
      $r= $this->response;
      $this->response= null;
      return $r;
    }

    return new HttpResponse(new MemoryInputStream("HTTP/1.0 200 Testing OK\r\n"));
  }

  /**
   * Send a HTTP request
   *
   * @param   peer.http.HttpRequest
   * @return  peer.http.HttpResponse response object
   */
  public function send(HttpRequest $request) {
    $this->lastRequest= $request->getRequestString();

    $this->cat && $this->cat->info('>>>', $request->getHeaderString());
    $response= $this->response();
    $this->cat && $this->cat->info('<<<', $response->getHeaderString());
    return $response;
  }

  /**
   * Send a HTTP request
   *
   * @param   peer.http.HttpRequest
   * @return  peer.http.MockHttpOutputStream
   */
  public function open(HttpRequest $request) {
    $this->lastRequest= $request->getRequestString();

    $this->cat && $this->cat->info('>>>', $request->getHeaderString());
    return new MockHttpOutputStream();
  }

  /**
   * Finish a HTTP request
   *
   * @param   peer.http.MockHttpOutputStream $output
   * @return  peer.http.HttpResponse
   */
  public function finish($output) {
    $this->lastRequest.= $output->bytes;

    $response= $this->response();
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
