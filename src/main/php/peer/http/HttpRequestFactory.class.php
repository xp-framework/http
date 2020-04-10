<?php namespace peer\http;

/**
 * Factory for HTTP / HTTPS. Used internally by the HttpConnection class.
 *
 * @see   xp://peer.http.HttpConnection
 */
class HttpRequestFactory {

  /**
   * Factory method
   *
   * @param   peer.URL an url object
   * @return  peer.http.HttpRequest a request object
   * @throws  lang.IllegalArgumentException in case the scheme is not supported
   */
  public static function factory($url) {
    switch ($url->getScheme()) {
      case 'http': case 'https':
        return new HttpRequest($url);
      
      default:
        throw new \lang\IllegalArgumentException('Scheme "'.$url->getScheme().'" not supported');
    }
  }
}