<?php namespace peer\http;

use lang\IllegalArgumentException;

/**
 * HTTP proxy
 *
 * @test  xp://peer.http.unittest.HttpProxyTest
 * @see   xp://peer.http.HttpConnection#setProxy
 */
class HttpProxy extends \lang\Object {
  const NONE = null;

  protected $host, $port, $excludes;
  
  /**
   * Constructor
   *
   * @param  string $host
   * @param  int $port default 8080
   * @param  string[] $excludes
   * @throws lang.IllegalArgumentException
   */
  public function __construct($host, $port= 8080, $excludes= []) {
    if (null === $port) {
      if (2 !== sscanf($host, '%[^:]:%d', $this->host, $this->port)) {
        throw new IllegalArgumentException('Malformed authority "'.$host.'"');
      }
    } else {
      $this->host= $host;
      $this->port= $port;
    }

    $this->excludes= array_merge(['localhost'], $excludes);
  }

  /** @return string */
  public function host() { return $this->host; }

  /** @return int */
  public function port() { return $this->port; }

  /** @return string[] */
  public function excludes() { return $this->excludes; }

  /**
   * Check whether a given URL is excluded
   *
   * @param   peer.URL $url
   * @return  bool
   */
  public function isExcluded(\peer\URL $url) {
    foreach ($this->excludes as $pattern) {
      if (stristr($url->getHost(), $pattern)) return true;
    }
    return false;
  }
}
