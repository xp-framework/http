<?php namespace peer\http\io;

use io\streams\InputStream;

/**
 * Writes a request to a given output. The methods are guaranteed to be
 * called in the following order:
 *
 * - request()
 * - header()[, header()[, ...]]
 * - commit()
 * - [body()]
 */
interface To {

  /**
   * Sends request line
   *
   * @param  string $verb One of "GET", "HEAD", "POST", etc.
   * @param  string $target The URI
   * @param  string $version HTTP version to use, e.g. "1.1"
   * @return void
   */
  public function request($verb, $target, $version);

  /**
   * Sends header
   *
   * @param  string $name
   * @param  string $value
   * @return void
   */
  public function header($name, $value);

  /**
   * Commits request
   *
   * @return void
   */
  public function commit();

  /**
   * Sends body
   *
   * @param  io.streams.InputStream $in Where to read body from
   * @return void
   */
  public function body(InputStream $in);
}