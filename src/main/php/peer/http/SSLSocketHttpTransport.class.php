<?php namespace peer\http;

use peer\SSLSocket;

/**
 * Transport via SSL sockets
 *
 * @ext  openssl
 * @see  xp://peer.SSLSocket
 * @see  xp://peer.http.HttpConnection
 */
class SSLSocketHttpTransport extends SocketHttpTransport {

  /**
   * Creates a socket - overridden from parent class
   *
   * @param   peer.URL $url
   * @param   string $arg
   * @return  peer.Socket
   */
  protected function newSocket(\peer\URL $url, $arg) {
    sscanf($arg, 'v%d', $version);
    return new SSLSocket($url->getHost(), $url->getPort(443), null, $version);
  }
}
