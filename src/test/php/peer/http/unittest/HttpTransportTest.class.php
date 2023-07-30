<?php namespace peer\http\unittest;

use lang\{ClassLoader, IllegalArgumentException};
use peer\URL;
use peer\http\{HttpProxy, HttpTransport};
use test\{Assert, Before, Expect, Test};

class HttpTransportTest {

  #[Before]
  public static function registerTransport() {
    HttpTransport::register('test', ClassLoader::defineClass('TestHttpTransport', 'peer.http.HttpTransport', [], '{
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

  #[Test, Expect(IllegalArgumentException::class)]
  public function registerIncorrectClass() {
    HttpTransport::register('irrelevant', typeof($this));
  }

  #[Test]
  public function host() {
    $t= HttpTransport::transportFor(new URL('test://example.com'));
    Assert::equals('example.com', $t->host);
  }

  #[Test]
  public function port_80_is_the_default_port() {
    $t= HttpTransport::transportFor(new URL('test://example.com'));
    Assert::equals(80, $t->port);
  }

  #[Test]
  public function urls_may_contain_port() {
    $t= HttpTransport::transportFor(new URL('test://example.com:8080'));
    Assert::equals(8080, $t->port);
  }

  #[Test]
  public function schemes_may_contain_args() {
    $t= HttpTransport::transportFor(new URL('test+v2://example.com:443'));
    Assert::equals('v2', $t->arg);
  }

  #[Test]
  public function null_is_passed_if_scheme_has_no_arg() {
    $t= HttpTransport::transportFor(new URL('test://example.com:443'));
    Assert::null($t->arg);
  }
}