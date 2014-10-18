<?php namespace peer\http;

/**
 * HTTP proxy
 *
 * @see   xp://peer.http.HttpConnection#setProxy
 */
class HttpProxy extends \lang\Object {
  public $host, $port, $excludes;
  
  /**
   * Constructor
   *
   * @param   string $host
   * @param   int $port default 8080
   * @param   string[] $excludes
   */
  public function __construct($host, $port= 8080, $excludes= []) {
    if (null === $port) {
      sscanf($host, '%[^:]:%d', $this->host, $port);
      $this->port= $port ?: 8080;
    } else {
      $this->host= $host;
      $this->port= $port;
    }

    $this->excludes= array_merge(['localhost'], $excludes);
  }

  /**
   * Add a URL pattern to exclude.
   *
   * @param   string $pattern
   */
  public function addExclude($pattern) {
    $this->excludes[]= $pattern;
  }
  
  /**
   * Add a URL pattern to exclude and return this proxy. For use with
   * chained method calls.
   *
   * @param   string $pattern
   * @return  self this object
   */
  public function withExclude($pattern) {
    $this->excludes[]= $pattern;
    return $this;
  }

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
