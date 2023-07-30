<?php namespace peer\http\unittest;

use peer\Socket;
use peer\http\ChunkedHttpOutputStream;
use test\Assert;
use test\{Before, Test, TestCase};

class ChunkedHttpOutputStreamTest {

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

    Assert::equals('', $socket->written);
  }

  #[Test]
  public function flushed_when_buffer_size_exceeded() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket, 4);
    $stream->write('Testing');

    Assert::equals("7\r\nTesting\r\n", $socket->written);
  }

  #[Test]
  public function buffer_reset_after_flusing() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket, 4);
    $stream->write('Testing');
    $stream->write('Two');

    Assert::equals("7\r\nTesting\r\n", $socket->written);
  }

  #[Test]
  public function write_two_chunks() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket);
    $stream->write('One');
    $stream->write('Two');
    $stream->flush();

    Assert::equals("6\r\nOneTwo\r\n", $socket->written);
  }

  #[Test]
  public function without_buffering() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket, 0);
    $stream->write('Test');

    Assert::equals("4\r\nTest\r\n", $socket->written);
  }

  #[Test]
  public function flush() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket);
    $stream->write('Test');
    $stream->flush();

    Assert::equals("4\r\nTest\r\n", $socket->written);
  }

  #[Test]
  public function close() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket);
    $stream->close();

    Assert::equals("0\r\n\r\n", $socket->written);
  }

  #[Test]
  public function close_after_writing() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket);
    $stream->write('Test');
    $stream->close();

    Assert::equals("4\r\nTest\r\n0\r\n\r\n", $socket->written);
  }

  #[Test]
  public function close_twice() {
    $socket= $this->socket();
    $stream= new ChunkedHttpOutputStream($socket);
    $stream->close();
    $stream->close();

    Assert::equals("0\r\n\r\n", $socket->written);
  }
}