<?php namespace peer\http;

use peer\SSLSocket;
use peer\TLSSocket;
use io\IOException;

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
    if ('tls' === $arg) {
      return new TLSSocket($url->getHost(), $url->getPort(443), null);
    } else {
      sscanf($arg, 'v%d', $version);
      return new SSLSocket($url->getHost(), $url->getPort(443), null, $version);
    }
  }

  /**
   * Enable cryptography on a given socket
   *
   * @param  peer.Socket $s
   * @param  [:int] $methods
   * @return void
   * @throws io.IOException
   */
  protected function enable($s, $methods) {
    foreach ($methods as $name => $method) {
      if (stream_socket_enable_crypto($s->getHandle(), true, $method)) {
        $this->cat && $this->cat->debug('@@@ Enabling', $name, 'cryptography');
        return;
      }
    }
    throw new IOException('Cannot establish secure connection, tried '.\xp::stringOf($methods));
  }

  /**
   * Send proxy request
   *
   * @param  peer.Socket $s Connection to proxy
   * @param  peer.http.HttpRequest $request
   * @param  peer.URL $url
   */
  protected function proxy($s, $request, $url) {
    static $methods= [
      'tls://'    => STREAM_CRYPTO_METHOD_TLS_CLIENT,
      'sslv3://'  => STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
      'sslv23://' => STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
      'sslv2://'  => STREAM_CRYPTO_METHOD_SSLv2_CLIENT,
    ];

    $connect= sprintf(
      "CONNECT %1\$s:%2\$d HTTP/1.1\r\nHost: %1\$s:%2\$d\r\n\r\n",
      $url->getHost(),
      $url->getPort(443)
    );
    $this->cat && $this->cat->info('>>>', substr($connect, 0, strpos($connect, "\r")));
    $s->write($connect);
    $handshake= $s->read();

    if (4 === ($r= sscanf($handshake, "HTTP/%*d.%*d %d %[^\r]", $status, $message))) {
      while ($line= $s->readLine()) { $handshake.= $line."\n"; }
      $this->cat && $this->cat->info('<<<', $handshake);

      if (200 === $status) {
        stream_context_set_option($s->getHandle(), 'ssl', 'peer_name', $url->getHost());
        if (isset($methods[$this->socket->_prefix])) {
          $this->enable($s, [$this->socket->_prefix => $methods[$this->socket->_prefix]]);
        } else {
          $this->enable($s, $methods);
        }
      } else {
        throw new IOException('Cannot connect through proxy: #'.$status.' '.$message);
      }
    } else {
      throw new IOException('Proxy did not answer with valid HTTP: '.$handshake);
    }
  }
}
