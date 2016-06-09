<?php namespace peer\http;

use lang\IllegalArgumentException;
use peer\http\proxy\Excludes;

/**
 * HTTP proxy
 *
 * ```php
 * // Create using host and port
 * $proxy= new HttpProxy('proxy.example.com', 3128, $excludes);
 *
 * // Create using authority.
 * $proxy= new HttpProxy('proxy.example.com:3128', null, $excludes);
 * ```
 *
 * @see   xp://peer.http.HttpConnection#setProxy
 * @see   xp://peer.http.proxy.Excludes
 * @test  xp://peer.http.unittest.HttpProxyTest
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
      if ('[' === $host{0}) {
        $parsed= sscanf($host, '[%[^]]]:%d', $addr, $this->port);
        $this->host= '['.$addr.']';
      } else {
        $parsed= sscanf($host, '%[^:]:%d', $this->host, $this->port);
      }

      if (2 !== $parsed) {
        throw new IllegalArgumentException('Malformed authority "'.$host.'"');
      }
    } else {
      $this->host= $host;
      $this->port= $port;
    }

    $this->excludes= new Excludes(array_merge(['localhost'], $excludes));
  }

  /** @return string */
  public function host() { return $this->host; }

  /** @return int */
  public function port() { return $this->port; }

  /** @return peer.http.proxy.Excludes */
  public function excludes() { return $this->excludes; }
}
