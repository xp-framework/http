<?php namespace peer\http;

use io\streams\InputStream;
use io\IOException;

class ReadChunks implements InputStream {
  private $stream;
  private $available= -1;

  /**
   * Handle chunked transfer encoding. In chunked transfer encoding,
   * a hexadecimal number followed by optional text is on a line by
   * itself. The line is terminated by \r\n. The hexadecimal number
   * indicates the size of the chunk. The first chunk indicator comes
   * immediately after the headers. We ignore any chunk extensions.
   * For more details, see RFC 2616, section 3.6.1
   *
   * @return int
   */
  private function readChunk() {
    $indicator= $this->stream->readLine();
    if (!sscanf($indicator, "%x", $size)) {
      throw new IOException(sprintf(
        'Chunked transfer encoding: Indicator line "%s" invalid',
        addcslashes($indicator, "\0..\17")
      ));
    }
    return $size;
  }

  /**
   * Constructor
   *
   * @param  peer.http.HttpInputStream
   */
  public function __construct(HttpInputStream $stream) {
    $this->stream= $stream;
    $this->available= $this->readChunk();
  }
  
  /**
   * Read a string
   *
   * @param   int $limit default 8192
   * @return  string
   */
  public function read($limit= 8192) {
    $chunk= $this->stream->read($this->available);
    $this->stream->readLine();

    // Last chunk has zero length
    if (0 === ($this->available= $this->readChunk())) {
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
    return $this->available;
  }

  /**
   * Close this buffer
   *
   * @return void
   */
  public function close() {
    $this->stream->close();
  }
}
