<?php namespace peer\http\unittest;

use peer\URL;
use peer\http\HttpTransport;
use peer\http\HttpProxy;

/**
 * TestCase
 *
 * @see      xp://peer.http.HttpTransport
 */
class HttpTransportTest extends \unittest\TestCase {

  /**
   * Register test transport
   */
  #[@beforeClass]
  public static function registerTransport() {
    HttpTransport::register('test', \lang\ClassLoader::defineClass('TestHttpTransport', 'peer.http.HttpTransport', array(), '{
      public $host, $port, $arg, $proxy;

      public function __construct(\peer\URL $url, $arg) {
        $this->host= $url->getHost();
        $this->port= $url->getPort(80);
        $this->arg= $arg;
      }

      public function setProxy(\peer\http\HttpProxy $proxy= null) {
        $this->proxy= $proxy;
      }
      
      public function send(\peer\http\HttpRequest $request, $timeout= 60, $connecttimeout= 2.0) {
        // Not implemented
      }
    }'));
  }

  #[@test, @expect('lang.IllegalArgumentException')]
  public function registerIncorrectClass() {
    HttpTransport::register('irrelevant', $this->getClass());
  }

  #[@test]
  public function host() {
    $t= HttpTransport::transportFor(new URL('test://example.com'));
    $this->assertEquals('example.com', $t->host);
  }

  #[@test]
  public function port_80_is_the_default_port() {
    $t= HttpTransport::transportFor(new URL('test://example.com'));
    $this->assertEquals(80, $t->port);
  }

  #[@test]
  public function urls_may_contain_port() {
    $t= HttpTransport::transportFor(new URL('test://example.com:8080'));
    $this->assertEquals(8080, $t->port);
  }

  #[@test]
  public function schemes_may_contain_args() {
    $t= HttpTransport::transportFor(new URL('test+v2://example.com:443'));
    $this->assertEquals('v2', $t->arg);
  }

  #[@test]
  public function null_is_passed_if_scheme_has_no_arg() {
    $t= HttpTransport::transportFor(new URL('test://example.com:443'));
    $this->assertNull($t->arg);
  }

  #[@test]
  public function scheme_proxy_is_used() {
    with ($proxy= new HttpProxy('proxy', 3128)); {
      HttpTransport::$PROXIES['test']= $proxy;
      $t= HttpTransport::transportFor(new URL('test://example.com'));
      unset(HttpTransport::$PROXIES['test']);
      $this->assertEquals($proxy, $t->proxy);
    }
  }

  #[@test]
  public function all_proxy_is_used() {
    with ($proxy= new HttpProxy('proxy', 3128)); {
      HttpTransport::$PROXIES['*']= $proxy;
      $t= HttpTransport::transportFor(new URL('test://example.com'));
      unset(HttpTransport::$PROXIES['*']);
      $this->assertEquals($proxy, $t->proxy);
    }
  }

  #[@test]
  public function no_proxy_is_used_if_scheme_does_not_match() {
    with ($proxy= new HttpProxy('proxy', 3128)); {
      HttpTransport::$PROXIES['anonther-scheme']= $proxy;
      $t= HttpTransport::transportFor(new URL('test://example.com'));
      unset(HttpTransport::$PROXIES['anonther-scheme']);
      $this->assertNull($t->proxy);
    }
  }
}
