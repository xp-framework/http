<?php namespace peer\http\unittest;

use peer\Socket;
use peer\http\ChunkedHttpOutputStream;
use unittest\{Before, Test, TestCase};

class ChunkedHttpOutputStreamTest extends TestCase {

  /** Creates a socket for testing */
  private function socket(): Socket {
    return new class('test', 6100) extends Socket {
      public $written= '';

      public function write($bytes) {
        $this->written.= $bytes;
      }
    };
  }

  #[Test]
  public function can_create() {
    new ChunkedHttpOutputStream($this->socket());
  }

  #[Test]
  public function write_buffers() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket);
    $stream->write('Test');

    $this->assertEquals('', $socket->written);
  }

  #[Test]
  public function flushed_when_buffer_size_exceeded() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket, 4);
    $stream->write('Testing');

    $this->assertEquals("7\r\nTesting\r\n", $socket->written);
  }

  #[Test]
  public function buffer_reset_after_flusing() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket, 4);
    $stream->write('Testing');
    $stream->write('Two');

    $this->assertEquals("7\r\nTesting\r\n", $socket->written);
  }

  #[Test]
  public function write_two_chunks() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket);
    $stream->write('One');
    $stream->write('Two');
    $stream->flush();

    $this->assertEquals("6\r\nOneTwo\r\n", $socket->written);
  }

  #[Test]
  public function without_buffering() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket, 0);
    $stream->write('Test');

    $this->assertEquals("4\r\nTest\r\n", $socket->written);
  }

  #[Test]
  public function flush() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket);
    $stream->write('Test');
    $stream->flush();

    $this->assertEquals("4\r\nTest\r\n", $socket->written);
  }

  #[Test]
  public function close() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket);
    $stream->close();

    $this->assertEquals("0\r\n\r\n", $socket->written);
  }

  #[Test]
  public function close_after_writing() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket);
    $stream->write('Test');
    $stream->close();

    $this->assertEquals("4\r\nTest\r\n0\r\n\r\n", $socket->written);
  }

  #[Test]
  public function close_twice() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket);
    $stream->close();
    $stream->close();

    $this->assertEquals("0\r\n\r\n", $socket->written);
  }
}