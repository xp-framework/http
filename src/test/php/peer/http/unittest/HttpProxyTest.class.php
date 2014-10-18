<?php namespace peer\http\unittest;

use peer\URL;
use peer\http\HttpProxy;

/**
 * TestCase
 *
 * @see      xp://peer.http.HttpProxy
 */
class HttpProxyTest extends \unittest\TestCase {

  #[@test]
  public function no_proxy() {
    $this->assertNull(HttpProxy::NONE);
  }

  #[@test]
  public function host() {
    $proxy= new HttpProxy('proxy.example.com', 3128);
    $this->assertEquals('proxy.example.com', $proxy->host());
  }

  #[@test]
  public function port_is_8080_if_omitted() {
    $proxy= new HttpProxy('proxy.example.com');
    $this->assertEquals(8080, $proxy->port());
  }

  #[@test]
  public function port() {
    $proxy= new HttpProxy('proxy.example.com', 3128);
    $this->assertEquals(3128, $proxy->port());
  }

  #[@test]
  public function excludes_contains_localhost_by_default() {
    $proxy= new HttpProxy('proxy.example.com', 3128);
    $this->assertEquals(['localhost'], $proxy->excludes());
  }

  #[@test]
  public function localhost_always_present() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['internal.example.com']);
    $this->assertEquals(['localhost', 'internal.example.com'], $proxy->excludes());
  }

  #[@test]
  public function localhost_not_added_multiple_times() {
    $proxy= new HttpProxy('proxy.example.com', 3128);
    $this->assertEquals(['localhost'], $proxy->excludes(['localhost']));
  }

  #[@test]
  public function can_create_with_authority() {
    $proxy= new HttpProxy('proxy.example.com:3128', null);
    $this->assertEquals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }

  #[@test, @expect('lang.IllegalArgumentException')]
  public function cannot_create_with_host_only_authority() {
    new HttpProxy('proxy.example.com', null);
  }

  #[@test, @values([
  #  ['http://internal.example.com/index.html', true],
  #  ['http://internal.example.com:80/index.html', true],
  #  ['http://internal.example.com:8081/api', true],
  #  ['http://beta.internal.example.com/', true],
  #  ['http://sub.beta.internal.example.com/', true],
  #  ['https://internal.example.com:443/login', true],
  #  ['https://SAP.INTERNAL.EXAMPLE.COM', true],
  #  ['https://example.com/', false]
  #])]
  public function host_in_excludes_is_excluded($url, $expected) {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['internal.example.com']);
    $this->assertEquals($expected, $proxy->isExcluded(new URL($url)));
  }

  #[@test, @values([
  #  ['http://internal.example.com/', true],
  #  ['https://extranet.example.com/', true],
  #  ['https://www.example.com/', true],
  #  ['https://SAP.INTERNAL.EXAMPLE.COM', true],
  #  ['https://example.com/', false]
  #])]
  public function exclude_starting_with_dot($url, $expected) {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['.example.com']);
    $this->assertEquals($expected, $proxy->isExcluded(new URL($url)));
  }


  #[@test, @values([
  #  ['http://internal.example.com/index.html', true],
  #  ['http://internal.example.com:80/index.html', true],
  #  ['http://beta.internal.example.com/', true],
  #  ['http://sub.beta.internal.example.com/', true],
  #  ['https://SAP.INTERNAL.EXAMPLE.COM:80', true],
  #  ['http://internal.example.com:8081/api', false]
  #])]
  public function host_with_port_in_includes_matches_port($url, $expected) {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['internal.example.com:80']);
    $this->assertEquals($expected, $proxy->isExcluded(new URL($url)));
  }

  #[@test]
  public function asterisk_in_excludes_for_overriding_proxy_completely() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['*']);
    $this->assertTrue($proxy->isExcluded(new URL('http://example.com/')));
  }

  #[@test]
  public function exludes_also_work_with_ip_addresses() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['example.com']);
    $resolved= dns_get_record('example.com', DNS_A);
    $this->assertTrue($proxy->isExcluded(new URL('http://'.$resolved[0]['ip'])), \xp::stringOf($resolved));
  }
}
