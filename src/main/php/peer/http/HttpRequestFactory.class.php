<?php namespace peer\http;

use peer\URL;


/**
 * Request factory. Used internally by the HttpConnection class.
 *
 * @see      xp://peer.http.HttpConnection
 * @purpose  Factory for HTTP / HTTPS
 */
class HttpRequestFactory extends \lang\Object {

  /**
   * Factory method
   *
   * @param   peer.URL an url object
   * @return  lang.Object a request object
   * @throws  lang.IllegalArgumentException in case the scheme is not supported
   */
  public static function factory($url) {
    switch ($url->getScheme()) {
      case 'http':
      case 'https':
        return new HttpRequest($url);
      
      default:
        throw new \lang\IllegalArgumentException('Scheme "'.$url->getScheme().'" not supported');
    }
  }
}
