<?php namespace peer\http\io;

use io\streams\InputStream;

/**
 * Writes request to a string for debugging
 */
class ToDebug extends \lang\Object implements To {
  private $bytes;

  /** @return string */
  public function bytes() { return $this->bytes; }

  /**
   * Sends request line
   *
   * @param  string $verb One of "GET", "HEAD", "POST", etc.
   * @param  string $target The URI
   * @param  string $version HTTP version to use, e.g. "1.1"
   * @return void
   */
  public function request($verb, $target, $version) {
    $this->bytes= $verb.' '.$target.' '.$version."\r\n";
  }

  /**
   * Sends header
   *
   * @param  string $name
   * @param  string $value
   * @return void
   */
  public function header($name, $value) {
    $this->bytes.= $name.': '.$value."\r\n";
  }

  /**
   * Commits request
   *
   * @return void
   */
  public function commit() {
    $this->bytes.= "\r\n";
  }

  /**
   * Sends body
   *
   * @param  io.streams.InputStream $in Where to read body from
   * @return void
   */
  public function body(InputStream $in) {
    $this->bytes.= '('.$in->toString().')';
  }
}