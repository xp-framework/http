<?php namespace peer\http;

use io\streams\InputStream;

/**
 * InputStream that reads from a HTTP Response
 *
 * @test  xp://peer.http.unittest.HttpInputStreamTest
 */
class HttpInputStream implements InputStream {
  private $stream;
  private $buffer= '';

  /**
   * Constructor
   *
   * @param  io.streams.InputStream
   * @param  callable $consumed Optional callback when stream is completely consumed
   */
  public function __construct(InputStream $stream, $consumed= null) {
    $this->stream= $stream;
    $this->consumed= $consumed;
  }

  /**
   * Put given bytes back into read buffer
   *
   * @param  string $bytes
   * @return void
   */
  public function pushBack($bytes) {
    $this->buffer= $bytes.$this->buffer;
  }

  /** @param callable $consumed */
  public function callback($consumed) {
    $this->consumed= $consumed;
  }

  /** @return void */
  public function consumed() {
    if ($f= $this->consumed) $f();
  }

  /** @return bool */
  public function available() {
    if ('' === $this->buffer) {
      return $this->stream->available();
    } else {
      return strlen($this->buffer);
    }
  }

  /** @return string */
  public function read($limit= 8192) {
    if (null === $this->buffer) {
      return null;    // EOF
    } else if ('' === $this->buffer) {
      $chunk= $this->stream->read($limit);
      return '' == $chunk ? null : $chunk;
    } else {
      $return= substr($this->buffer, 0, $limit);
      $this->buffer= (string)substr($this->buffer, $limit);
      return $return;
    }
  }

  /** @return string */
  public function readLine() {
    if (null === $this->buffer) return null;    // EOF

    while (false === ($p= strpos($this->buffer, "\r\n"))) {
      $chunk= $this->stream->read();
      if ('' == $chunk) {
        $return= $this->buffer;
        $this->buffer= null;
        return $return;
      }
      $this->buffer.= $chunk;
    }

    $return= substr($this->buffer, 0, $p);
    $this->buffer= substr($this->buffer, $p + 2);
    return $return;
  }

  /** @return voud */
  public function close() {
    $this->stream->close();
  }
}