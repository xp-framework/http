<?php namespace peer\http\unittest;

use io\streams\MemoryInputStream;
use lang\IllegalStateException;
use peer\http\Authorizations;
use peer\http\BasicAuthorization;
use peer\http\HttpResponse;
use security\SecureString;
use unittest\TestCase;
use util\Secret;

class AuthorizationsTest extends TestCase {
  const USER= 'foo';
  private $cut;

  /** @return void */
  public function setUp() {
    $this->cut= new Authorizations();
  }

  /** @return var[][] */
  private function secrets() {
    $values= [];
    if (class_exists('util\\Secret')) {
      $values[]= [new Secret('Test')];
    }
    if (class_exists('security\\SecureString')) {
      $values[]= [new SecureString('Test')];
    }
    return $values;
  }

  #[@test, @values('secrets')]
  public function create_basic_auth($secret) {
    $res= new HttpResponse(new MemoryInputStream(
      "HTTP/1.1 401 Authentication required.\r\n".
      "WWW-Authenticate: Basic realm=\"Auth me!\"\r\n\r\n"
    ));

    $this->assertInstanceof(
      'peer.http.BasicAuthorization',
      $this->cut->create($res, self::USER, $secret)
    );
  }

  #[@test, @values('secrets')]
  public function create_digest_auth($secret) {
    $res= new HttpResponse(new MemoryInputStream(
      "HTTP/1.1 401 Authentication required.\r\n".
      "WWW-Authenticate: Digest realm=\"Auth me!\", qop=\"auth\", nonce=\"12345\"\r\n\r\n"
    ));

    $this->assertInstanceof(
      'peer.http.DigestAuthorization',
      $this->cut->create($res, self::USER, $secret)
    );
  }

  #[@test, @values('secrets'), @expect(IllegalStateException::class)]
  public function unknown_type_throws_exception($secret) {
    $res= new HttpResponse(new MemoryInputStream(
      "HTTP/1.1 401 Authentication required.\r\n".
      "WWW-Authenticate: Bloafed realm=\"Auth me!\", qop=\"auth\", nonce=\"12345\"\r\n\r\n"
    ));

    $this->cut->create($res, self::USER, $secret);
  }

  #[@test]
  public function requires_a_401() {
    $res= new HttpResponse(new MemoryInputStream('HTTP/1.1 401 Authentication required.'."\r\n\r\n"));
    $this->assertTrue($this->cut->required($res));
  }

  #[@test]
  public function not_required_without_401() {
    $res= new HttpResponse(new MemoryInputStream('HTTP/1.1 200 Ok'."\r\n\r\n"));
    $this->assertFalse($this->cut->required($res));
  }
}