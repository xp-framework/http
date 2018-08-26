<?php namespace peer\http;

class SocketHttpOutputStream extends HttpOutputStream {
  public $socket;

  /** @param peer.Socket $socket */
  public function __construct($socket) {
    $this->socket= $socket;
  }

  /**
   * Write given bytes
   *
   * @param  string $bytes
   * @return void
   */
  public function write($bytes) {
    $this->socket->write($bytes);
  }
}