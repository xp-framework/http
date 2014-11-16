<?php namespace peer\http\io;

use io\streams\InputStream;
use util\log\LogCategory;

/**
 * Writes request to a string for debugging
 */
class ToLog extends \lang\Object implements To {
  private $cat, $header;

  /**
   * Creates an instance writing to a stream
   *
   * @param  util.log.LogCategory $cat
   * @param  string $prefix
   */
  public function __construct(LogCategory $cat, $prefix= null) {
    $this->cat= $cat;
    $this->header= null === $prefix ? '' : $prefix.' ';
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
    $this->header.= $verb.' '.$target.' HTTP/'.$version."\n";
  }

  /**
   * Sends header
   *
   * @param  string $name
   * @param  string $value
   * @return void
   */
  public function header($name, $value) {
    $this->header.= $name.': '.$value."\n";
  }

  /**
   * Commits request
   *
   * @return void
   */
  public function commit() {
    $this->cat->info($this->header);
  }

  /**
   * Sends body
   *
   * @param  io.streams.InputStream $in Where to read body from
   * @return void
   */
  public function body(InputStream $in) {
    $this->cat->info('Uploading from', $in);
  }
}