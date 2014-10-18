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
   * Returns a function which matches the given host
   *
   * @param  string $host
   * @return function(string): bool
   */
  protected function match($host) {
    if (preg_match('/^([0-9]{1,3}.)+[0-9]{1,3}$/', $host)) {
      return function($exclude) use($host) {
        $resolved= dns_get_record($exclude, DNS_A);
        return $resolved && $resolved[0]['ip'] === $host;
      };
    } else {
      return function($exclude) use($host) {
        return 0 === substr_compare($host, $exclude, -strlen($exclude), strlen($exclude), true);
      };
    }
  }

  /**
   * Check whether a given URL is excluded
   *
   * @param   peer.URL $url
   * @return  bool
   */
  public function isExcluded(\peer\URL $url) {
    static $ports= ['http' => 80, 'https' => 443];

    $match= $this->match($url->getHost());
    foreach ($this->excludes as $exclude) {
      if ('*' === $exclude) {
        $matches= true;
      } else if (false === ($p= strpos($exclude, ':'))) {
        $matches= $match($exclude);
      } else {
        $matches= (
          $match(substr($exclude, 0, $p)) &&
          $url->getPort(@$ports[$url->getScheme()]) === (int)substr($exclude, $p + 1)
        );
      }
      if ($matches) return true;
    }
    return false;
  }
}
