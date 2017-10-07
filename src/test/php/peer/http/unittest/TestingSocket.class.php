<?php namespace peer\http\unittest;

class TestingSocket extends \peer\Socket {
  private $open, $eof;
  private $sent= [];
  private $receive= [];
  private $connected= [];

  public function receives(...$bytes) { $this->receive= $bytes; }

  public function connected() { return $this->connected; }

  public function connect() {
    $this->connected[]= $this->host.':'.$this->port;
    $this->open= true;
    $this->eof= false;
  }

  public function isConnected() {
    return $this->open;
  }

  public function close() {
    $this->open= false; 
    $this->eof= true;
  }

  public function eof() {
    return $this->eof;
  }

  public function write($bytes) {
    $this->sent[]= $bytes;
  }

  public function readBinary($bytes= 8192) {
    if (empty($this->receive)) {
      $this->eof= true;
      return '';
    }

    return array_shift($this->receive);
  }
}