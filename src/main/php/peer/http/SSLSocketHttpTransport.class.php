<?php namespace peer\http;

use io\IOException;
use peer\CryptoSocket;
use peer\URL;
use util\Objects;

/**
 * Transport via SSL sockets
 *
 * @ext  openssl
 * @see  xp://peer.SSLSocket
 * @see  xp://peer.http.HttpConnection
 */
class SSLSocketHttpTransport extends SocketHttpTransport {
  private static $crypto= [
    'tls'    => STREAM_CRYPTO_METHOD_TLS_CLIENT,
    'tlsv10' => STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT,
    'tlsv11' => STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT,
    'tlsv12' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
    'sslv3'  => STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
    'sslv23' => STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
    'sslv2'  => STREAM_CRYPTO_METHOD_SSLv2_CLIENT
  ];

  static function __static() {

    // See https://github.com/php/php-src/commit/5c05f5e6d3d31a03c152fe90697bdc3e33193ced, PHP 7.4+
    if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
      self::$crypto['tlsv13']= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
    }
  }

  /**
   * Creates a socket - overridden from parent class
   *
   * @param   peer.URL $url
   * @param   string $arg
   * @return  peer.Socket
   */
  protected function newSocket(URL $url, $arg) {
    $socket= new CryptoSocket($url->getHost(), $url->getPort(443));
    if (null === $arg) {
      $socket->cryptoImpl= STREAM_CRYPTO_METHOD_ANY_CLIENT;
    } else if ('v' === $arg[0]) {
      $socket->cryptoImpl= self::$crypto['ssl'.$arg];
    } else {
      $socket->cryptoImpl= self::$crypto[$arg];
    }
    return $socket;
  }

  /**
   * Send proxy request
   *
   * @param  peer.Socket $s Connection to proxy
   * @param  peer.http.HttpRequest $request
   * @param  peer.URL $url
   * @return void
   * @throws io.IOException
   */
  protected function proxy($s, $request, $url) {
    $connect= sprintf(
      "CONNECT %1\$s:%2\$d HTTP/1.1\r\nHost: %1\$s:%2\$d\r\n\r\n",
      $url->getHost(),
      $url->getPort(443)
    );
    $this->cat && $this->cat->info('>>>', substr($connect, 0, strpos($connect, "\r")));
    $s->write($connect);

    // Verify we are actually talking to a HTTP proxy
    $handshake= $s->read();
    if (4 !== ($r= sscanf($handshake, "HTTP/%*d.%*d %d %[^\r]", $status, $message))) {
      throw new IOException('Proxy did not answer with valid HTTP: '.$handshake);
    }

    // Verify proxy answers with a 200 status code
    while ($line= $s->readLine()) $handshake.= $line."\n";
    $this->cat && $this->cat->info('<<<', $handshake);
    if (200 !== $status) {
      throw new IOException('Cannot connect through proxy: #'.$status.' '.$message);
    }

    // Enable cryptography
    stream_context_set_option($s->getHandle(), 'ssl', 'peer_name', $url->getHost());
    if (!stream_socket_enable_crypto($s->getHandle(), true, $this->socket->cryptoImpl)) {
      $methods= '';
      foreach (self::$crypto as $name => $flag) {
        if ($flag === $this->socket->cryptoImpl & $flag) $methods.= ', '.$name;
      }
      throw new IOException('Cannot establish secure connection, tried '.substr($methods, 2));
    }

    $this->cat && $this->cat->debug('@@@ Enabled cryptography: '.Objects::stringOf(stream_get_meta_data($s->getHandle())['crypto']));
  }
}
