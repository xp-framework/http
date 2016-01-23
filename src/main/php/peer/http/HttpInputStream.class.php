<?php namespace peer\http;

use io\streams\InputStream;

/**
 * InputStream that reads from a HTTP Response
 *
 * @test  xp://peer.http.unittest.HttpInputStreamTest
 */
class HttpInputStream extends \lang\Object implements InputStream {
  protected
    $response  = null,
    $buffer    = '',
    $available = 0;
  
  /**
   * Constructor
   *
   * @param   peer.http.HttpResponse $response
   */
  public function __construct(HttpResponse $response) {
    $this->response= $response;
  }
  
  /**
   * Buffer a chunk if necessary
   *
   * @return  int available
   */
  protected function buffer() {
    if (($l= strlen($this->buffer)) > 0) return $l;
    if (false === ($chunk= $this->response->readData(8192, true))) {
      $this->available= -1;
      return 0;
    } else {
      $this->buffer.= $chunk;
      $this->available= strlen($this->buffer);
      return $this->available;
    }
  }

  /**
   * Read a string
   *
   * @param   int $limit default 8192
   * @return  string
   */
  public function read($limit= 8192) {
    if (-1 === $this->available) return null;   // At end
    $this->buffer();
    $b= substr($this->buffer, 0, $limit);
    $this->buffer= substr($this->buffer, $limit);
    return $b;
  }

  /**
   * Returns the number of bytes that can be read from this stream 
   * without blocking.
   *
   * @return  int
   */
  public function available() {
    return (-1 === $this->available) ? 0 : $this->buffer();
  }

  /**
   * Close this buffer
   */
  public function close() {
    $this->response->closeStream();
  }

  /**
   * Creates a string representation of this Http
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'<'.$this->response->toString().'>';
  }
}
