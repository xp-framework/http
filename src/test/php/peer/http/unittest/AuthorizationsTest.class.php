<?php namespace peer\http\unittest;

use io\streams\MemoryInputStream;
use lang\IllegalStateException;
use peer\http\{Authorizations, BasicAuthorization, DigestAuthorization, HttpResponse};
use security\SecureString;
use test\{Assert, Before, Expect, Test, Values};
use util\Secret;

class AuthorizationsTest {
  const USER= 'foo';

  private $fixture;

  #[Before]
  public function fixture() {
    $this->fixture= new Authorizations();
  }

  /** @return iterable */
  private function secrets() {
    $values= [];
    if (class_exists(Secret::class)) {
      $values[]= [new Secret('Test')];
    }
    if (class_exists(SecureString::class)) {
      $values[]= [new SecureString('Test')];
    }
    return $values;
  }

  #[Test, Values(from: 'secrets')]
  public function create_basic_auth($secret) {
    $res= new HttpResponse(new MemoryInputStream(
      "HTTP/1.1 401 Authentication required.\r\n".
      "WWW-Authenticate: Basic realm=\"Auth me!\"\r\n\r\n"
    ));

    Assert::instance(BasicAuthorization::class, $this->fixture->create($res, self::USER, $secret));
  }

  #[Test, Values(from: 'secrets')]
  public function create_digest_auth($secret) {
    $res= new HttpResponse(new MemoryInputStream(
      "HTTP/1.1 401 Authentication required.\r\n".
      "WWW-Authenticate: Digest realm=\"Auth me!\", qop=\"auth\", nonce=\"12345\"\r\n\r\n"
    ));

    Assert::instance(DigestAuthorization::class, $this->fixture->create($res, self::USER, $secret));
  }

  #[Test, Values(from: 'secrets'), Expect(IllegalStateException::class)]
  public function unknown_type_throws_exception($secret) {
    $res= new HttpResponse(new MemoryInputStream(
      "HTTP/1.1 401 Authentication required.\r\n".
      "WWW-Authenticate: Bloafed realm=\"Auth me!\", qop=\"auth\", nonce=\"12345\"\r\n\r\n"
    ));

    $this->fixture->create($res, self::USER, $secret);
  }

  #[Test]
  public function requires_a_401() {
    $res= new HttpResponse(new MemoryInputStream('HTTP/1.1 401 Authentication required.'."\r\n\r\n"));
    Assert::true($this->fixture->required($res));
  }

  #[Test]
  public function not_required_without_401() {
    $res= new HttpResponse(new MemoryInputStream('HTTP/1.1 200 Ok'."\r\n\r\n"));
    Assert::false($this->fixture->required($res));
  }
}