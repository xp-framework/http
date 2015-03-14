<?php namespace peer\http;

use peer\URL;
use peer\Socket;
use peer\SocketInputStream;
use peer\http\io\ToStream;
use peer\http\io\ToLog;

/**
 * Transport via sockets
 *
 * @see  xp://peer.Socket
 * @see  xp://peer.http.HttpConnection
 */
class SocketHttpTransport extends HttpTransport {
  protected $socket= null;
  protected $proxySocket= null;

  static function __static() { }

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
  public function setProxy(HttpProxy $proxy= null) {
    parent::setProxy($proxy);
    $this->proxySocket= $proxy ? new Socket($proxy->host(), $proxy->port()) : null;
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
   * Send proxy request
   *
   * @param  peer.Socket $s Connection to proxy
   * @param  peer.http.HttpRequest $request
   * @param  peer.URL $url
   */
  protected function proxy($s, $request, $url) {
    $request->setTarget(sprintf(
      '%s://%s%s%s',
      $url->getScheme(),
      $url->getHost(),
      $url->getPort() ? ':'.$url->getPort() : '',
      $url->getPath('/')
    ));
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
    if ($this->proxy && !$this->proxy->excludes()->contains($url= $request->getUrl())) {
      $s= $this->connect($this->proxySocket, $timeout, $connecttimeout);
      $this->proxy($s, $request, $url);
    } else {
      $s= $this->connect($this->socket, $timeout, $connecttimeout);
    }

    $this->cat && $request->write(new ToLog($this->cat, '>>>'));
    $request->write(new ToStream($s->out()));

    $response= new HttpResponse(new SocketInputStream($s));
    $this->cat && $this->cat->info('<<<', $response->getHeaderString());
    return $response;
  }
}
