<?php namespace peer\http;

use peer\SocketInputStream;

class Channel implements \io\streams\InputStream {
  private $socket;
  private $reuseable= null;

  /** @param peer.Socket */
  public function __construct($socket) {
    $this->socket= $socket;
  }

  /** @return peer.Socket */
  public function socket() { return $this->socket; }

  /**
   * Rebinds to a new socket, closing the existing one if necessary
   *
   * @param  peer.Socket
   * @return void
   */
  public function bind($socket) {
    if ($this->socket->isConnected()) {
      $this->socket->close();
    }
    $this->socket= $socket;
  }

  /**
   * Connect (if necessary)
   *
   * @param  float $connectTimeout
   * @param  float $readTimeout
   * @return void
   */
  public function connect($connectTimeout, $readTimeout) {
    if (false === $this->reuseable) {
      $this->socket->close();
    } else if ($this->socket->isConnected()) {
      return;
    }

    $this->socket->setTimeout($readTimeout);
    $this->socket->connect($connectTimeout);
  }

  /**
   * Sends a request and returns the response
   *
   * @param  peer.http.HttpRequest $request
   * @return peer.http.HttpResponse
   */
  public function send($request) {
    $this->socket->write($request->getRequestString());
    $this->reuseable= false;
    return new HttpResponse($this, true, function() { $this->reuseable= true; });
  }

  /** @return int */
  public function available() {
    return $this->socket->eof() ? 0 : 1;
  }

  /** @return string */
  public function read($limit= 8192) {
    return $this->socket->readBinary($limit);
  }

  /** @return void */
  public function close() {
    // NOOP
  }
}