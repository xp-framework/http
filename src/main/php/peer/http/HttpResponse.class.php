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
 * @see   https://en.wikipedia.org/wiki/HTTP_persistent_connection
 */
class HttpResponse implements Value {
  public
    $statuscode    = 0,
    $message       = '',
    $version       = '',
    $headers       = [];
  
  protected
    $in            = null,
    $_headerlookup = [];

  /**
   * Constructor
   *
   * @param  io.streams.InputStream $stream
   * @param  bool $chunked Whether to check for chunked encoding.
   */
  public function __construct(InputStream $stream, $chunked= true) {
    $input= $stream instanceof HttpInputStream ? $stream : new HttpInputStream($stream);
    do {
      $message= $input->readLine();
      $r= sscanf($message, "HTTP/%[0-9.] %3d %[^\r]", $this->version, $this->statuscode, $this->message);
      if ($r < 2) {
        throw new \lang\FormatException('"'.addcslashes($message, "\0..\37!\177..\377").'" is not a valid HTTP response ['.$r.']');
      }
      $this->readHeaders($input);
    } while (100 === $this->statuscode);

    if ($this->version < '1.1' || (
      isset($this->_headerlookup['connection']) &&
      $this->headers[$this->_headerlookup['connection']][0] === 'close'
    )) {
      $input->callback(null);
    }

    if ($chunked && (
      isset($this->_headerlookup['transfer-encoding']) &&
      $this->headers[$this->_headerlookup['transfer-encoding']][0] === 'chunked'
    )) {
      $this->in= new ReadChunks($input);
    } else if (isset($this->_headerlookup['content-length'])) {
      $this->in= new ReadLength($input, (int)$this->headers[$this->_headerlookup['content-length']][0]);
    } else {
      $this->in= $input;
    }
  }

  /**
   * Reads headers
   *
   * @param  peer.http.HttpInputStream $stream
   * @return void
   */
  private function readHeaders($stream) {
    while ($line= $stream->readLine()) {
      sscanf($line, "%[^:]: %[^\r]", $name, $value);

      $l= strtolower($name);
      if (isset($this->_headerlookup[$l])) {
        $name= $this->_headerlookup[$l];
      } else {
        $this->_headerlookup[$l]= $name;
      }
      if (!isset($this->headers[$name])) {
        $this->headers[$name]= [$value];
      } else {
        $this->headers[$name][]= $value;
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
    if ($this->in->available()) {
      return $this->in->read($size);
    } else {
      $this->in->close();
      return false;
    }
  }
  
  /**
   * Closes the stream and returns FALSE
   *
   * @return  bool
   */
  public function closeStream() {
    $this->in->close();
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
  public function in() { return $this->in; }

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
