<?php namespace peer\http;

use peer\URL;
use peer\Socket;
use peer\SocketInputStream;

/**
 * Transport via sockets
 *
 * @see  xp://peer.Socket
 * @see  xp://peer.http.HttpConnection
 */
class SocketHttpTransport extends HttpTransport {
  protected $socket= null;
  protected $proxySocket= null;

  /**
   * Constructor
   *
   * @param   peer.URL $url
   * @param   string $arg
   */
  public function __construct(URL $url, $arg) {
    $this->socket= $this->newSocket($url, $arg);
  }

  /**
   * Creates a socket
   *
   * @param   peer.URL $url
   * @param   string $arg
   * @return  peer.Socket
   */
  protected function newSocket(URL $url, $arg) {
    return new Socket($url->getHost(), $url->getPort(80));
  }

  /**
   * Set proxy
   *
   * @param   peer.http.HttpProxy proxy
   */
  public function setProxy(HttpProxy $proxy) {
    parent::setProxy($proxy);
    $this->proxySocket= new Socket($proxy->host, $proxy->port);
  }

  /**
   * Connect to a socket. If socket still open from last request (which
   * is the case when unread data is left on it by not reading the body,
   * e.g.), use the quick & dirty way: Close and reopen!
   *
   * @param  peer.Socket $s
   * @param  double $read Read timeout
   * @param  double $connect Connect timeout
   * @return peer.Socket
   */
  protected function connect($s, $read, $connect) {
    $s->isConnected() && $s->close();
    $s->setTimeout($read);
    $s->connect($connect);
    return $s;
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
        $this->cat && $this->cat->info('@@@ Enabling', $name, 'cryptography');
        return;
      }
    }
    throw new \io\IOException('Cannot establish secure connection, tried '.\xp::stringOf($methods));
  }
  
  /**
   * Sends a request
   *
   * @param   peer.http.HttpRequest request
   * @param   int timeout default 60
   * @param   float connecttimeout default 2.0
   * @return  peer.http.HttpResponse response object
   */
  public function send(HttpRequest $request, $timeout= 60, $connecttimeout= 2.0) {

    // Use proxy socket and Modify target if a proxy is to be used for this request, 
    // a proxy wants "GET http://example.com/ HTTP/X.X" for (and "CONNECT" for HTTPs).
    if ($this->proxy && !$this->proxy->isExcluded($url= $request->getUrl())) {
      $s= $this->connect($this->proxySocket, $timeout, $connecttimeout);
      if ('http' === $url->getScheme()) {
        $request->setTarget(sprintf(
          '%s://%s%s%s',
          $url->getScheme(),
          $url->getHost(),
          $url->getPort() ? ':'.$url->getPort() : '',
          $url->getPath('/')
        ));
      } else {
        $connect= sprintf(
          "CONNECT %1\$s:%2\$d HTTP/1.1\r\nHost: %1\$s:%2\$d\r\n\r\n",
          $url->getHost(),
          $url->getPort(443)
        );
        $this->cat && $this->cat->info('>>>', $connect);
        $s->write($connect);
        $handshake= $s->read();
        $this->cat && $this->cat->info('<<<', $handshake);
        sscanf($handshake, "HTTP/%*d.%*d %d %[^\r]", $status, $message);
        if (200 === $status) {
          $s->read();
          $this->enable($s, [
            'TLS'    => STREAM_CRYPTO_METHOD_TLS_CLIENT,
            'SSLv3'  => STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
            'SSLv23' > STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
            'SSLv2'  => STREAM_CRYPTO_METHOD_SSLv2_CLIENT
          ]);
        } else {
          return new HttpResponse(new \io\streams\MemoryInputStream($handshake));
        }
      }
    } else {
      $s= $this->connect($this->socket, $timeout, $connecttimeout);
    }

    $s->write($request->getRequestString());

    $this->cat && $this->cat->info('>>>', $request->getHeaderString());
    $response= new HttpResponse(new SocketInputStream($s));
    $this->cat && $this->cat->info('<<<', $response->getHeaderString());
    return $response;
  }
}
