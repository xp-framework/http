<?php namespace peer\http\unittest;

use peer\http\Channel;
use peer\http\HttpRequest;
use peer\Socket;
use peer\SocketException;
use peer\URL;
use io\streams\Streams;

class ChannelTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Channel(new Socket('localhost', 80));
  }

  #[@test]
  public function socket_accessor() {
    $s= new Socket('localhost', 80);
    $this->assertEquals($s, (new Channel($s))->socket());
  }

  #[@test]
  public function bind_to_another_socket() {
    $s= new Socket('localhost', 80);
    $c= new Channel(new Socket('localhost', 8080));
    $c->bind($s);
    $this->assertEquals($s, $c->socket());
  }

  #[@test]
  public function bind_closes_socket() {
    $s= new TestingSocket('localhost', 80);
    $s->connect();
    $c= new Channel($s);
    $c->bind(new Socket('localhost', 8080));
    $this->assertFalse($s->isConnected());
  }

  #[@test]
  public function disconnect_closes_socket() {
    $s= new TestingSocket('localhost', 80);
    $s->connect();

    $c= new Channel($s);
    $c->disconnect();
    $this->assertFalse($s->isConnected());
  }

  #[@test]
  public function sending_request_opens_socket() {
    $s= new TestingSocket('localhost', 80);
    $s->receives("HTTP/1.1 200 OK\r\n\r\n");
    $c= new Channel($s);
    $c->send(new HttpRequest(new URL('http://localhost:80/')));
    $this->assertEquals(['localhost:80'], $s->connected());
  }

  #[@test, @expect(SocketException::class)]
  public function exception_raised_when_eof_received() {
    $c= new Channel(new TestingSocket('localhost', 80));

    $c->send(new HttpRequest(new URL('http://localhost:80/')));
  }

  #[@test]
  public function connection_is_kept_alive_by_default() {
    $s= new TestingSocket('localhost', 80);
    $c= new Channel($s);

    for ($i= 0; $i < 2; $i++) {
      $s->receives("HTTP/1.1 200 OK\r\nContent-Length: 4\r\n\r\nTest");
      Streams::readAll($c->send(new HttpRequest(new URL('http://localhost:80/')))->in());
    }

    $this->assertEquals(['localhost:80'], $s->connected());
  }

  #[@test]
  public function connection_closed_when_specified_by_server() {
    $s= new TestingSocket('localhost', 80);
    $c= new Channel($s);

    for ($i= 0; $i < 2; $i++) {
      $s->receives("HTTP/1.1 200 OK\r\nConnection: close\r\nContent-Length: 4\r\n\r\nTest");
      Streams::readAll($c->send(new HttpRequest(new URL('http://localhost:80/')))->in());
    }

    $this->assertEquals(['localhost:80', 'localhost:80'], $s->connected());
  }

  #[@test]
  public function connection_closed_when_content_is_not_transferred() {
    $s= new TestingSocket('localhost', 80);
    $c= new Channel($s);

    for ($i= 0; $i < 2; $i++) {
      $s->receives("HTTP/1.1 200 OK\r\nContent-Length: 4\r\n\r\nTest");
      $c->send(new HttpRequest(new URL('http://localhost:80/')));
    }

    $this->assertEquals(['localhost:80', 'localhost:80'], $s->connected());
  }
}