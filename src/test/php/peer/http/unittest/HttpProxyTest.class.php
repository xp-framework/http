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

  #[@test]
  public function host_in_excludes_is_excluded() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['internal.example.com']);
    $this->assertTrue($proxy->isExcluded(new URL('http://internal.example.com/index.html')));
  }

  #[@test]
  public function host_in_excludes_is_excluded_regardless_of_port() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['internal.example.com']);
    $this->assertTrue($proxy->isExcluded(new URL('http://internal.example.com:8081/index.html')));
  }

  #[@test]
  public function host_with_port_in_includes_matches_port() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['internal.example.com:80']);
    $this->assertTrue($proxy->isExcluded(new URL('http://internal.example.com:80/index.html')));
  }

  #[@test]
  public function host_with_port_in_includes_matches_default_port() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['internal.example.com:80']);
    $this->assertTrue($proxy->isExcluded(new URL('http://internal.example.com/index.html')));
  }

  #[@test]
  public function another_port_is_not_excluded() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['internal.example.com:80']);
    $this->assertFalse($proxy->isExcluded(new URL('http://internal.example.com:8080/index.html')));
  }

  #[@test]
  public function another_host_is_not_excluded() {
    $proxy= new HttpProxy('proxy.example.com', 3128, ['internal.example.com']);
    $this->assertFalse($proxy->isExcluded(new URL('http://www.example.com/')));
  }
}
