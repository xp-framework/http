<?php namespace peer\http\unittest;

use lang\IllegalArgumentException;
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
    $this->assertEquals(['localhost'], $proxy->excludes()->patterns());
  }

  #[@test]
  public function localhost_always_present() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['internal.example.com']);
    $this->assertEquals(['localhost', 'internal.example.com'], $proxy->excludes()->patterns());
  }

  #[@test]
  public function localhost_not_added_multiple_times() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['localhost', 'localhost']);
    $this->assertEquals(['localhost'], $proxy->excludes()->patterns());
  }

  #[@test]
  public function can_create_with_authority() {
    $proxy= new HttpProxy('proxy.example.com:3128', null);
    $this->assertEquals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }

  #[@test]
  public function can_create_with_ipv6_addr() {
    $proxy= new HttpProxy('[::1]:3128', null);
    $this->assertEquals(['[::1]', 3128], [$proxy->host(), $proxy->port()]);
  }

  #[@test, @expect(IllegalArgumentException::class)]
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
    $this->assertEquals($expected, $proxy->excludes()->contains(new URL($url)));
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
    $this->assertEquals($expected, $proxy->excludes()->contains(new URL($url)));
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
    $this->assertEquals($expected, $proxy->excludes()->contains(new URL($url)));
  }

  #[@test]
  public function asterisk_in_excludes_for_overriding_proxy_completely() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['*']);
    $this->assertTrue($proxy->excludes()->contains(new URL('http://example.com/')));
  }

  #[@test]
  public function exludes_also_work_localhost_special_case() {
    $proxy= new HttpProxy('proxy.example.com');
    $this->assertTrue($proxy->excludes()->contains(new URL('http://127.0.0.1')));
  }

  /** @return string */
  protected function exampleIp() {
    static $resolved= null;

    if (!$resolved) {
      if (!($resolved= dns_get_record('example.com', DNS_A))) {
        $this->skip('Cannot resolve example.com (DNS_A)');
      }
    }
    return $resolved[0]['ip'];
  }

  #[@test]
  public function host_excludes_work_with_ips_in_urls() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['example.com']);
    $this->assertTrue($proxy->excludes()->contains(new URL('http://'.$this->exampleIp())));
  }

  #[@test]
  public function ips_in_both_excludes_and_urls_work() {
    $proxy= new HttpProxy('proxy.example.com', 3128, [$this->exampleIp()]);
    $this->assertTrue($proxy->excludes()->contains(new URL('http://'.$this->exampleIp())));
  }

  #[@test]
  public function ip_excludes_work_with_hosts_in_urls() {
    $proxy= new HttpProxy('proxy.example.com', 3128, [$this->exampleIp()]);
    $this->assertTrue($proxy->excludes()->contains(new URL('http://example.com')));
  }

  #[@test, @values([
  #  ['https://192.168.2.6/', true],
  #  ['https://192.168.3.6/', false]
  #])]
  public function cidr($url, $expected) {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['192.168.2.0/24']);
    $this->assertEquals($expected, $proxy->excludes()->contains(new URL($url)));
  }
}