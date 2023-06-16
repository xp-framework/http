<?php namespace peer\http;

/** @test peer.http.unittest.ChunkedHttpOutputStreamTest */
class ChunkedHttpOutputStream extends HttpOutputStream {
  public $socket;
  private $size, $buffer= '', $closed= false;

  /**
   * Creates a new chunked output stream on given socket and with
   * a given buffer size, which defaults to 4096.
   *
   * @param  peer.Socket $socket
   * @param  int $buffer
   */
  public function __construct($socket, $buffer= 4096) {
    $this->socket= $socket;
    $this->size= $buffer;
    $this->buffer= '';
  }

  /**
   * Write given bytes
   *
   * @param  string $bytes
   * @return void
   */
  public function write($bytes) {
    $this->buffer.= $bytes;
    if (strlen($this->buffer) > $this->size) {
      $this->socket->write(dechex(strlen($this->buffer))."\r\n".$this->buffer."\r\n");
      $this->buffer= '';
    }
  }

  /**
   * Flush this stream explicitely
   *
   * @return void
   */
  public function flush() {
    if ('' === $this->buffer) return;

    $this->socket->write(dechex(strlen($this->buffer))."\r\n".$this->buffer."\r\n");
    $this->buffer= '';
  }

  /** @return void */
  public function close() {
    if ($this->closed) return;

    if ('' === $this->buffer) {
      $this->socket->write("0\r\n\r\n");
    } else {
      $this->socket->write(dechex(strlen($this->buffer))."\r\n".$this->buffer."\r\n0\r\n\r\n");
    }
    $this->closed= true;
  }
}