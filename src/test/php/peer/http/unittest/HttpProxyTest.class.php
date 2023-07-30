<?php namespace peer\http\unittest;

use lang\IllegalArgumentException;
use peer\URL;
use peer\http\HttpProxy;
use test\Assert;
use test\{Expect, Test, Values};

/**
 * TestCase
 *
 * @see      xp://peer.http.HttpProxy
 */
class HttpProxyTest {

  #[Test]
  public function no_proxy() {
    Assert::null(HttpProxy::NONE);
  }

  #[Test]
  public function host() {
    $proxy= new HttpProxy('proxy.example.com', 3128);
    Assert::equals('proxy.example.com', $proxy->host());
  }

  #[Test]
  public function port_is_8080_if_omitted() {
    $proxy= new HttpProxy('proxy.example.com');
    Assert::equals(8080, $proxy->port());
  }

  #[Test]
  public function port() {
    $proxy= new HttpProxy('proxy.example.com', 3128);
    Assert::equals(3128, $proxy->port());
  }

  #[Test]
  public function excludes_contains_localhost_by_default() {
    $proxy= new HttpProxy('proxy.example.com', 3128);
    Assert::equals(['localhost'], $proxy->excludes()->patterns());
  }

  #[Test]
  public function localhost_always_present() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['internal.example.com']);
    Assert::equals(['localhost', 'internal.example.com'], $proxy->excludes()->patterns());
  }

  #[Test]
  public function localhost_not_added_multiple_times() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['localhost', 'localhost']);
    Assert::equals(['localhost'], $proxy->excludes()->patterns());
  }

  #[Test]
  public function can_create_with_authority() {
    $proxy= new HttpProxy('proxy.example.com:3128', null);
    Assert::equals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }

  #[Test]
  public function can_create_with_ipv6_addr() {
    $proxy= new HttpProxy('[::1]:3128', null);
    Assert::equals(['[::1]', 3128], [$proxy->host(), $proxy->port()]);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function cannot_create_with_host_only_authority() {
    new HttpProxy('proxy.example.com', null);
  }

  #[Test, Values([['http://internal.example.com/index.html', true], ['http://internal.example.com:80/index.html', true], ['http://internal.example.com:8081/api', true], ['http://beta.internal.example.com/', true], ['http://sub.beta.internal.example.com/', true], ['https://internal.example.com:443/login', true], ['https://SAP.INTERNAL.EXAMPLE.COM', true], ['https://example.com/', false]])]
  public function host_in_excludes_is_excluded($url, $expected) {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['internal.example.com']);
    Assert::equals($expected, $proxy->excludes()->contains(new URL($url)));
  }

  #[Test, Values([['http://internal.example.com/', true], ['https://extranet.example.com/', true], ['https://www.example.com/', true], ['https://SAP.INTERNAL.EXAMPLE.COM', true], ['https://example.com/', false]])]
  public function exclude_starting_with_dot($url, $expected) {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['.example.com']);
    Assert::equals($expected, $proxy->excludes()->contains(new URL($url)));
  }


  #[Test, Values([['http://internal.example.com/index.html', true], ['http://internal.example.com:80/index.html', true], ['http://beta.internal.example.com/', true], ['http://sub.beta.internal.example.com/', true], ['https://SAP.INTERNAL.EXAMPLE.COM:80', true], ['http://internal.example.com:8081/api', false]])]
  public function host_with_port_in_includes_matches_port($url, $expected) {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['internal.example.com:80']);
    Assert::equals($expected, $proxy->excludes()->contains(new URL($url)));
  }

  #[Test]
  public function asterisk_in_excludes_for_overriding_proxy_completely() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['*']);
    Assert::true($proxy->excludes()->contains(new URL('http://example.com/')));
  }

  #[Test]
  public function exludes_also_work_localhost_special_case() {
    $proxy= new HttpProxy('proxy.example.com');
    Assert::true($proxy->excludes()->contains(new URL('http://127.0.0.1')));
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

  #[Test]
  public function host_excludes_work_with_ips_in_urls() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['example.com']);
    Assert::true($proxy->excludes()->contains(new URL('http://'.$this->exampleIp())));
  }

  #[Test]
  public function ips_in_both_excludes_and_urls_work() {
    $proxy= new HttpProxy('proxy.example.com', 3128, [$this->exampleIp()]);
    Assert::true($proxy->excludes()->contains(new URL('http://'.$this->exampleIp())));
  }

  #[Test]
  public function ip_excludes_work_with_hosts_in_urls() {
    $proxy= new HttpProxy('proxy.example.com', 3128, [$this->exampleIp()]);
    Assert::true($proxy->excludes()->contains(new URL('http://example.com')));
  }

  #[Test, Values([['https://192.168.2.6/', true], ['https://192.168.3.6/', false]])]
  public function cidr($url, $expected) {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['192.168.2.0/24']);
    Assert::equals($expected, $proxy->excludes()->contains(new URL($url)));
  }
}