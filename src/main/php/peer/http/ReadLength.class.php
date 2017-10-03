<?php namespace peer\http;

use io\streams\InputStream;

class ReadLength implements InputStream {
  private $stream, $length;
  
  /**
   * Constructor
   *
   * @param  peer.http.HttpInputStream
   */
  public function __construct(HttpInputStream $stream, $length) {
    $this->stream= $stream;
    $this->length= $length;
  }
  
  /**
   * Read a string
   *
   * @param   int $limit default 8192
   * @return  string
   */
  public function read($limit= 8192) {
    $chunk= $this->stream->read(min($this->length, $limit));

    // If remaining length is 0, we've completely consumed the content
    if (0 === ($this->length-= strlen($chunk))) {
      $this->stream->consumed();
    }
    return $chunk;
  }

  /**
   * Returns the number of bytes that can be read from this stream 
   * without blocking.
   *
   * @return  int
   */
  public function available() {
    return $this->length;
  }

  /**
   * Close this buffer
   */
  public function close() {
    $this->stream->close();
  }
}
