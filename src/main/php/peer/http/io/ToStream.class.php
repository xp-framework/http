<?php namespace peer\http\io;

use io\streams\InputStream;
use io\streams\OutputStream;

/**
 * Writes request to an OutputStream
 *
 * @test  xp://peer.http.unittest.ToStreamTest
 */
class ToStream extends \lang\Object implements To {
  private $out;

  /**
   * Creates an instance writing to a stream
   *
   * @param  io.streams.OutputStream $out
   */
  public function __construct(OutputStream $out) {
    $this->out= $out;
  }

  /**
   * Sends request line
   *
   * @param  string $verb One of "GET", "HEAD", "POST", etc.
   * @param  string $target The URI
   * @param  string $version HTTP version to use, e.g. "1.1"
   * @return void
   */
  public function request($verb, $target, $version) {
    $this->out->write($verb.' '.$target.' '.$version."\r\n");
  }

  /**
   * Sends header
   *
   * @param  string $name
   * @param  string $value
   * @return void
   */
  public function header($name, $value) {
    $this->out->write($name.': '.$value."\r\n");
  }

  /**
   * Commits request
   *
   * @return void
   */
  public function commit() {
    $this->out->write("\r\n");
  }

  /**
   * Sends body
   *
   * @param  io.streams.InputStream $in Where to read body from
   * @return void
   */
  public function body(InputStream $in) {
    while ($in->available()) {
      $this->out->write($in->read());
    }
  }
}