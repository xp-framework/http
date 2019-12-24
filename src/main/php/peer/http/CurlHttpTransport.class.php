<?php namespace peer\http;

use io\IOException;
use io\streams\MemoryInputStream;
use peer\URL;

/**
 * Transport via curl functions
 *
 * @ext   curl
 * @see   xp://peer.http.HttpConnection
 */
class CurlHttpTransport extends HttpTransport {
  private $ssl= null;

  static function __static() { }

  /**
   * Constructor
   *
   * @param   peer.URL $url
   * @param   string $arg
   */
  public function __construct(URL $url, $arg) {
    sscanf($arg, 'v%d', $this->ssl);
  }

  /**
   * Opens a request
   *
   * @param   peer.http.HttpRequest $request
   * @param   float $connectTimeout default 2.0
   * @param   float $readTimeout default 60.0
   * @return  peer.http.HttpOutputStream
   * @throws  io.IOException
   */
  public function open(HttpRequest $request, $connectTimeout= 2.0, $readTimeout= 60.0) {
    static $versions= [
      HttpConstants::VERSION_1_0 => CURL_HTTP_VERSION_1_0,
      HttpConstants::VERSION_1_1 => CURL_HTTP_VERSION_1_1,
    ];

    $headers= [];
    foreach ($request->headers as $name => $values) {
      foreach ($values as $value) {
        $headers[]= $name.': '.$value;
      }
    }

    $handle= curl_init();
    curl_setopt_array($handle, [
      CURLOPT_HEADER         => true,
      CURLOPT_NOBODY         => 'HEAD' === $request->method,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYHOST => 2,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_URL            => $request->url->getCanonicalURL(),
      CURLOPT_CUSTOMREQUEST  => $request->method,
      CURLOPT_CONNECTTIMEOUT => $connectTimeout,
      CURLOPT_TIMEOUT        => $readTimeout,
      CURLOPT_HTTPHEADER     => $headers,
      CURLOPT_HTTP_VERSION   => isset($versions[$request->version]) ? $versions[$request->version] : CURL_HTTP_VERSION_NONE,
      CURLOPT_SSLVERSION     => $this->ssl,
    ]);

    if ($this->proxy && !$this->proxy->excludes()->contains($request->getUrl())) {
      curl_setopt_array($handle, [
        CURLOPT_PROXY     => $this->proxy->host(),
        CURLOPT_PROXYPORT => $this->proxy->port(),
      ]);
      $proxied= true;
    } else {
      $proxied= false;
    }

    $this->cat && $this->cat->info('>>>',
      $request->method.' '.$request->target().' HTTP/'.$request->version."\r\n".
      implode("\r\n", $headers)
    );
    return new CurlHttpOutputStream($handle, $proxied);
  }

  /**
   * Finishes a transfer and returns the response
   *
   * @param  peer.http.HttpOutputStream $stream
   * @return peer.http.HttpResponse
   * @throws  io.IOException
   */
  public function finish($stream) {
    $stream->close();
    if ('' !== $stream->bytes) {
      curl_setopt($stream->handle, CURLOPT_POSTFIELDS, $stream->bytes);
    }

    $transfer= curl_exec($stream->handle);
    if (false === $transfer) {
      $error= curl_errno($stream->handle);
      $message= curl_error($stream->handle);
      curl_close($stream->handle);
      throw new IOException($error.' '.$message);
    }

    // Strip "HTTP/x.x 200 Connection established" which is followed by
    // the real HTTP message: headers and body
    if ($stream->proxied) {
      $transfer= preg_replace('#^HTTP/[0-9]\.[0-9] [0-9]{3} .+\r\n\r\n#', '', $transfer);
    }

    curl_close($stream->handle);
    $response= new HttpResponse(new MemoryInputStream($transfer), false);
    $this->cat && $this->cat->info('<<<', $response->getHeaderString());
    return $response;
  }

  /**
   * Sends a request
   *
   * @param   peer.http.HttpRequest $request
   * @param   int $timeout default 60
   * @param   float $connecttimeout default 2.0
   * @return  peer.http.HttpResponse response object
   * @throws  io.IOException
   */
  public function send(HttpRequest $request, $timeout= 60, $connecttimeout= 2.0) {
    static $versions= [
      HttpConstants::VERSION_1_0 => CURL_HTTP_VERSION_1_0,
      HttpConstants::VERSION_1_1 => CURL_HTTP_VERSION_1_1,
    ];

    $headers= [];
    foreach ($request->headers as $name => $values) {
      foreach ($values as $value) {
        $headers[]= $name.': '.$value;
      }
    }

    $handle= curl_init();
    curl_setopt_array($handle, [
      CURLOPT_HEADER         => true,
      CURLOPT_NOBODY         => 'HEAD' === $request->method,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYHOST => 2,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_URL            => $request->url->getCanonicalURL(),
      CURLOPT_CUSTOMREQUEST  => $request->method,
      CURLOPT_CONNECTTIMEOUT => $connecttimeout,
      CURLOPT_TIMEOUT        => $timeout,
      CURLOPT_HTTPHEADER     => $headers,
      CURLOPT_HTTP_VERSION   => isset($versions[$request->version]) ? $versions[$request->version] : CURL_HTTP_VERSION_NONE,
      CURLOPT_SSLVERSION     => $this->ssl,
    ]);

    if ($this->proxy && !$this->proxy->excludes()->contains($request->getUrl())) {
      curl_setopt_array($handle, [
        CURLOPT_PROXY     => $this->proxy->host(),
        CURLOPT_PROXYPORT => $this->proxy->port(),
      ]);
      $read= function($transfer) {
        if (preg_match('#^HTTP/[0-9]\.[0-9] [0-9]{3} .+\r\n\r\n#', $transfer, $matches)) {

          // Strip "HTTP/x.x 200 Connection established" which is followed by
          // the real HTTP message: headers and body
          return substr($transfer, strlen($matches[0]));
        } else {
          return $transfer;
        }
      };
    } else {
      $read= function($transfer) { return $transfer; };
    }
    
    $return= curl_exec($handle);

    if (false === $return) {
      $errno= curl_errno($handle);
      $error= curl_error($handle);
      curl_close($handle);
      throw new \io\IOException(sprintf('%d: %s', $errno, $error));
    }
    // ensure handle is closed
    curl_close($handle);

    $this->cat && $this->cat->info('>>>', $request->getHeaderString());
    $response= new HttpResponse(new MemoryInputStream($read($return)), false);
    $this->cat && $this->cat->info('<<<', $response->getHeaderString());
    return $response;
  }
}
