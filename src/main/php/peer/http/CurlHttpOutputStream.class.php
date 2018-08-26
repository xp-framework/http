<?php namespace peer\http;

class CurlHttpOutputStream extends HttpOutputStream {
  public $bytes= '';
  public $handle, $proxied;

  public function __construct($handle, $proxied) {
    $this->handle= $handle;
    $this->proxied= $proxied;
  }

  /**
   * Write given bytes
   *
   * @param  string $bytes
   * @return void
   */
  public function write($bytes) {
    $this->bytes.= $bytes;
  }
}