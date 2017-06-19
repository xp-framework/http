<?php namespace peer\http\proxy;

use peer\URL;

/**
 * Excludes for a proxy may consist of the following:
 *
 * - A hostname, e.g. `example.com`. Matches all domains and subdomains.
 * - Same, but with leading dot: `.example.com`.
 * - An IPv4 address, e.g. `127.0.0.1`.
 * - An IPv4 netmask, e.g. `192.168.2.0/24`.
 *
 * @see   xp://peer.http.HttpProxy
 */
class Excludes {
  protected $patterns;

  /**
   * Creates a new instances
   *
   * @param  string[] $patterns
   */
  public function __construct($patterns) {
    $this->patterns= [];
    foreach ($patterns as $pattern) {
      $this->patterns[$pattern]= $this->match($pattern);
    }
  }

  /** @return string */
  public function patterns() { return array_keys($this->patterns); }

  /**
   * Creates a match based on a given exclude pattern
   *
   * @param  string $exclude
   * @return function(peer.URL): bool
   */
  protected function match($exclude) {
    if ('*' === $exclude) {
      return function($url) { return true; };
    } else if (false === ($p= strpos($exclude, ':'))) {
      $port= null;
    } else {
      $num= (int)substr($exclude, $p + 1);
      $port= function($url) use($num) {
        static $ports= ['http' => 80, 'https' => 443];
        return $num === $url->getPort(@$ports[$url->getScheme()]);
      };
      $exclude= substr($exclude, 0, $p);
    }

    preg_match('/^(([0-9]+\.)+[0-9]+)(\/([0-9]+))?$/', $exclude, $matches);
    if (isset($matches[4])) {
      $bits= (int)$matches[4];
      $ip= ip2long($matches[1]);
      $mask= 0 === $bits ? 0 : (~0 << (32 - $bits));
      $low= $ip & $mask;
      $high= $ip | (~$mask & 0xffffffff);

      $host= function($url) use($low, $high) {
        $check= ip2long(gethostbyname($url->getHost()));
        return $check >= $low && $check <= $high;
      };
    } else if (isset($matches[1])) {
      $ip= $matches[1];
      $host= function($url) use($ip) { return gethostbyname($url->getHost()) === $ip; };
    } else {
      $host= function($url) use($exclude) {
        if (preg_match('/^([0-9]+\.)+[0-9]+$/', $url->getHost())) {
          return $url->getHost() === gethostbyname($exclude);
        } else {
          return 0 === substr_compare($url->getHost(), $exclude, -strlen($exclude), strlen($exclude), true);
        }
      };
    }

    return $port ? function($url) use($host, $port) { return $host($url) && $port($url); } : $host;
  }

  /**
   * Check whether a given URL is excluded
   *
   * @param   peer.URL $url
   * @return  bool
   */
  public function contains(URL $url) {
    foreach ($this->patterns as $match) {
      if ($match($url)) return true;
    }
    return false;
  }
}