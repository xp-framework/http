<?php namespace peer\http;

use io\streams\InputStream;
use lang\Value;
use util\Objects;

/**
 * HTTP response
 *
 * @see   xp://peer.http.HttpConnection
 * @test  xp://peer.http.unittest.HttpResponseTest
 * @test  xp://peer.http.unittest.HttpInputStreamTest
 */
class HttpResponse implements Value {
  public
    $statuscode    = 0,
    $message       = '',
    $version       = '',
    $headers       = [],
    $chunked       = null;
  
  protected
    $stream        = null,
    $buffer        = '',
    $_headerlookup = [];
    
  /**
   * Constructor
   *
   * @param  io.streams.InputStream $stream
   * @param  bool $chunked Whether to check for chunked encoding.
   */
  public function __construct(InputStream $stream, $chunked= true) {
    $this->stream= $stream;
    
    // Read status line and headers
    do { $this->readHeader(); } while (100 === $this->statuscode);

    // Check for chunked transfer encoding
    $this->chunked= $chunked && (bool)stristr($this->getHeader('Transfer-Encoding'), 'chunked');
  }
  
  /**
   * Scan stream until we we find a certain character
   *
   * @param   string char
   * @return  string
   */
  protected function scanUntil($char) {
    $pos= strpos($this->buffer, $char);
    
    // Found no line ending in buffer, read until we do!
    while (false === $pos) {
      if ($this->stream->available() <= 0) {
        $pos= strlen($this->buffer);
        break;
      }
      $this->buffer.= $this->stream->read();
      $pos= strpos($this->buffer, $char);
    }

    // Return line, remove from buffer
    $line= substr($this->buffer, 0, $pos);
    $this->buffer= substr($this->buffer, $pos+ 1);
    return $line;
  }

  /**
   * Read a chunk
   *
   * @param   int bytes
   * @return  string
   */
  protected function readChunk($bytes) {
    $len= strlen($this->buffer);
    
    // Not enough data, read until it's here!
    while ($len < $bytes) {
      if ($this->stream->available() <= 0) break;
      $this->buffer.= $this->stream->read();
      $len= strlen($this->buffer);
    }
    
    // Return chunk, remove from buffer
    $chunk= substr($this->buffer, 0, $bytes);
    $this->buffer= substr($this->buffer, $bytes);
    return $chunk;
  }
  
  /**
   * Reads the header (status line and key/value pairs).
   *
   * @throws  lang.FormatException
   */
  protected function readHeader() {
  
    // Status line
    $status= $this->scanUntil("\n");
    $r= sscanf($status, "HTTP/%[0-9.] %3d %[^\r]", $this->version, $this->statuscode, $this->message);
    if ($r < 2) {
      throw new \lang\FormatException('"'.addcslashes($status, "\0..\37!\177..\377").'" is not a valid HTTP response ['.$r.']');
    }

    // Headers
    while ($line= $this->scanUntil("\n")) {
      if (strlen($line) < 2) break;   // A line starting with \r\n indicates ends of headers

      $v= null;
      sscanf($line, "%[^:]: %[^\r\n]", $k, $v);
      $l= strtolower($k);
      if (!isset($this->_headerlookup[$l])) {
        $this->_headerlookup[$l]= $k;
      } else {
        $k= $this->_headerlookup[$l];
      }
      if (!isset($this->headers[$k])) {
        $this->headers[$k]= [$v];
      } else {
        $this->headers[$k][]= $v;
      }
    }
  }

  /**
   * Read data
   *
   * @param   int size default 8192 maximum size to read
   * @return  string buf or FALSE to indicate EOF
   */
  public function readData($size= 8192) {
    if (!$this->chunked) {
      if (!($buf= $this->readChunk($size))) {
        return $this->closeStream();
      }

      return $buf;
    }

    // Handle chunked transfer encoding. In chunked transfer encoding,
    // a hexadecimal number followed by optional text is on a line by
    // itself. The line is terminated by \r\n. The hexadecimal number
    // indicates the size of the chunk. The first chunk indicator comes 
    // immediately after the headers. Note: We assume that a chunked 
    // indicator line will never be longer than 1024 bytes. We ignore
    // any chunk extensions. We ignore the size and boolean parameters
    // to this method completely to ensure functionality. For more 
    // details, see RFC 2616, section 3.6.1
    if (!($indicator= $this->scanUntil("\n"))) return $this->closeStream();
    if (!(sscanf($indicator, "%x%s\r", $chunksize, $extension))) {
      $this->closeStream();
      throw new \io\IOException(sprintf(
        'Chunked transfer encoding: Indicator line "%s" invalid', 
        addcslashes($indicator, "\0..\17")
      ));
    }

    // A chunk of size 0 means we're at the end of the document. We 
    // read the next line but ignore any trailers.
    if (0 === $chunksize) {
      $this->readChunk(2);
      return $this->closeStream();
    }

    // A chunk is terminated by \r\n, so scan over two more characters
    $chunk= $this->readChunk($chunksize);
    $this->readChunk(2);
    return $chunk;
  }
  
  /**
   * Closes the stream and returns FALSE
   *
   * @return  bool
   */
  public function closeStream() {
    $this->stream->close();
    return false;
  }

  /**
   * Retrieve input stream
   *
   * @deprecated Use in() instead
   * @return  io.streams.InputStream
   */
  public function getInputStream() {
    return $this->in();
  }

  /** @return io.streams.InputStream */
  public function in() { return new HttpInputStream($this); }

  /**
   * Returns HTTP response headers as read from server
   *
   * @return  string
   */
  public function getHeaderString() {
    $s= 'HTTP/'.$this->version.' '.$this->statuscode.' '.$this->message."\r\n";
    foreach ($this->headers as $k => $v) {
      $s.= $k.': '.implode(', ', $v)."\r\n";
    }
    return $s."\r\n";
  }
  
  /** @return string */
  public function hashCode() {
    return Objects::hashOf([$this->version, $this->statuscode, $this->message, $this->headers]);
  }

  /** @return string */
  public function toString() {
    $h= '';
    foreach ($this->headers as $k => $v) {
      $h.= sprintf("  [%-20s] { %s }\n", $k, implode(', ', $v));
    }
    return sprintf(
      "%s (HTTP/%s %3d %s) {\n%s}",
      nameof($this),
      $this->version,
      $this->statuscode,
      $this->message,
      $h
    );
  }

  /**
   * Compare
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? Objects::compare(
          [$this->version, $this->statuscode, $this->message, $this->headers],
          [$value->version, $value->statuscode, $value->message, $value->headers]
        )
      : 1
    ;
  }

  /**
   * Get HTTP statuscode
   *
   * @deprecated Use statusCode() instead
   * @return  int status code
   */
  public function getStatusCode() {
    return $this->statuscode;
  }

  /**
   * Get HTTP statuscode
   *
   * @return  int status code
   */
  public function statusCode() {
    return $this->statuscode;
  }

  /**
   * Get HTTP message
   *
   * @deprecated Use message() instead
   * @return  string
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Get HTTP message
   *
   * @return  string
   */
  public function message() {
    return $this->message;
  }
  
  /**
   * Get response headers as an associative array
   *
   * @deprecated Use headers() instead
   * @return  [:string] headers
   */
  public function getHeaders() {
    $headers= [];
    foreach ($this->headers as $name => $values) {
      $headers[$name]= end($values);
    }
    return $headers;
  }

  /**
   * Get response headers as an associative array
   *
   * @return  [:string[]] headers
   */
  public function headers() {
    return $this->headers;
  }

  /**
   * Get response header by name
   * Note: The lookup is performed case-insensitive
   *
   * @deprecated Use header() instead
   * @return  string value or NULL if this header does not exist
   */
  public function getHeader($name) {
    $l= strtolower($name);
    return isset($this->_headerlookup[$l]) ? end($this->headers[$this->_headerlookup[$l]]) : null;
  }

  /**
   * Get response header by name
   * Note: The lookup is performed case-insensitive
   *
   * @return  string[] value or NULL if this header does not exist
   */
  public function header($name) {
    $l= strtolower($name);
    return isset($this->_headerlookup[$l]) ? $this->headers[$this->_headerlookup[$l]] : null;
  }
}