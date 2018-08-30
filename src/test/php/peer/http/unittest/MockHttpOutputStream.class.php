<?php namespace peer\http\unittest;

use peer\http\HttpOutputStream;

class MockHttpOutputStream extends HttpOutputStream {
  public $bytes= '';

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