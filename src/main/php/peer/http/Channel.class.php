<?php namespace peer\http;

use peer\SocketException;

/**
 * Channel manages I/O between client and server, implementing keep-alive
 *
 * @see   https://en.wikipedia.org/wiki/HTTP_persistent_connection
 * @see   https://tools.ietf.org/html/rfc7230#section-6.3
 * @test  xp://peer.http.unittest.ChannelTest
 */
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
   * Disconnect (if necessary)
   *
   * @return void
   */
  public function disconnect() {
    $this->socket->isConnected() && $this->socket->close();
  }

  /**
   * Sends a request and returns the response
   *
   * @param  peer.http.HttpRequest $request
   * @param  float $connectTimeout
   * @param  float $readTimeout
   * @return peer.http.HttpResponse
   */
  public function send($request, $connectTimeout= 2.0, $readTimeout= 60.0) {

    // Previous call didn't finish reading all data, connection is not reusable
    if (false === $this->reuseable) {
      $this->socket->close();
    }

    do {
      if ($this->socket->isConnected()) {
        $reused= true;
      } else {
        $reused= false;
        $this->socket->setTimeout($readTimeout);
        $this->socket->connect($connectTimeout);
      }

      $this->socket->write($request->getRequestString());
      $this->reuseable= false;
      $input= new HttpInputStream($this, function() { $this->reuseable= true; });

      // If we reused the connection and we receive EOF, disconnect & retry
      $line= $input->readLine();
      if ($this->socket->eof()) {
        if (!$reused) throw new SocketException('Received EOF (timeout: '.$readTimeout.' seconds)');
        $this->socket->close();
        continue;
      }

      // Success
      $input->pushBack($line."\r\n");
      return new HttpResponse($input, true);
    } while (true);
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