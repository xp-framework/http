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

  static function __static() { }

  /**
   * Constructor
   *
   * @param   peer.URL $url
   * @param   string $arg
   */
  public function __construct(URL $url, $arg) {
    $this->socket= $this->newSocket($url, $arg);
    $this->channel= new Channel($this->socket);
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
   * Set or unset proxy
   *
   * @param  peer.http.HttpProxy $proxy
   * @return void
   */
  public function setProxy(HttpProxy $proxy= null) {
    parent::setProxy($proxy);
    if (null === $proxy) {
      $this->channel->bind($this->socket);
    } else {
      $this->proxySocket= new Socket($proxy->host(), $proxy->port());
      $this->channel->bind($this->proxySocket);
    }
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
      $this->proxy($this->channel->socket(), $request, $url);
    }
    $this->channel->connect($connecttimeout, $timeout);

    $this->cat && $this->cat->info('>>>', $request->getHeaderString());
    $response= $this->channel->send($request);
    $this->cat && $this->cat->info('<<<', $response->getHeaderString());
    return $response;
  }
}
