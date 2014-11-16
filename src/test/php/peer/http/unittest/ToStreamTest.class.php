<?php namespace peer\http\unittest;

use peer\http\io\ToStream;
use io\streams\MemoryOutputStream;
use io\streams\MemoryInputStream;

/**
 * TestCase for HTTP connection
 *
 * @see      xp://peer.http.HttpConnection
 */
class ToStreamTest extends \unittest\TestCase {
  protected $out;

  /**
   * Creates out member.
   */
  public function setUp() {
    $this->out= new MemoryOutputStream();
  }

  #[@test]
  public function can_create() {
    new ToStream($this->out);
  }

  #[@test]
  public function request() {
    $fixture= new ToStream($this->out);
    $fixture->request('GET', '/', 'HTTP/1.0');
    $fixture->commit();
    $this->assertEquals("GET / HTTP/1.0\r\n\r\n", $this->out->getBytes());
  }

  #[@test]
  public function header() {
    $fixture= new ToStream($this->out);
    $fixture->request('GET', '/', 'HTTP/1.0');
    $fixture->header('Connection', 'close');
    $fixture->commit();
    $this->assertEquals("GET / HTTP/1.0\r\nConnection: close\r\n\r\n", $this->out->getBytes());
  }

  #[@test]
  public function headers() {
    $fixture= new ToStream($this->out);
    $fixture->request('GET', '/', 'HTTP/1.0');
    $fixture->header('Connection', 'close');
    $fixture->header('Host', 'example.com');
    $fixture->commit();
    $this->assertEquals("GET / HTTP/1.0\r\nConnection: close\r\nHost: example.com\r\n\r\n", $this->out->getBytes());
  }

  #[@test]
  public function body() {
    $fixture= new ToStream($this->out);
    $fixture->request('POST', '/', 'HTTP/1.0');
    $fixture->header('Content-Length', '4');
    $fixture->commit();
    $fixture->body(new MemoryInputStream('Test'));
    $this->assertEquals("POST / HTTP/1.0\r\nContent-Length: 4\r\n\r\nTest", $this->out->getBytes());
  }
}