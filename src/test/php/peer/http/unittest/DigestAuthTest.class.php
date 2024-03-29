<?php namespace peer\http\unittest;

use io\streams\MemoryInputStream;
use lang\{IllegalStateException, MethodNotImplementedException};
use peer\URL;
use peer\http\{Authorizations, DigestAuthorization, HttpConstants, HttpRequest, HttpResponse};
use security\SecureString;
use test\{Assert, Before, Expect, Test};
use util\Secret;

class DigestAuthTest {
  const USER = 'Mufasa';
  const PASS = 'Circle Of Life';
  const CNONCE = '0a4f113b';

  private $secret, $digest;

  /** @return void */
  #[Before]
  public function setUp() {
    if (class_exists(Secret::class)) {
      $this->secret= new Secret(self::PASS);
    } else {
      $this->secret= new SecureString(self::PASS);
    }

    $this->digest= new DigestAuthorization(
      'testrealm@host.com',
      'auth,auth-int',
      'dcd98b7102dd2f0e8b11d0f600bfb0c093',
      '5ccc069c403ebaf9f0171e9517f40e41'
    );
    $this->digest->cnonce(self::CNONCE); // Hardcode client nconce, so hashes will be static for the tests
    $this->digest->setUsername(self::USER);
    $this->digest->setPassword($this->secret);
  }

  public function newConnection() {
    $http= new MockHttpConnection(new URL('http://example.com:80/dir/index.html'));
    $http->setResponse(new HttpResponse(new MemoryInputStream(
      "HTTP/1.0 401 Unauthorized\r\n".
      'WWW-Authenticate: Digest realm="testrealm@host.com", '.
      'qop="auth,auth-int", '.
      'nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093", '.
      'opaque="5ccc069c403ebaf9f0171e9517f40e41"'."\r\n"
    )));
    return $http;
  }

  /**
   * Assertion helper
   *
   * @param  string $needle
   * @param  string $haystack
   * @throws unittest.AssertionFailedError
   */
  private function assertStringContains($needle, $haystack) {
    if (false === strpos($haystack, $needle)) {
      $this->fail('String not contained.', $haystack, $needle);
    }
  }

  #[Test]
  public function server_indicates_digest_auth() {
    Assert::equals(401, $this->newConnection()->get('/')->getStatusCode());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function no_auth_when_not_indicated() {
    Authorizations::fromResponse(new HttpResponse(new MemoryInputStream("HTTP/1.0 200 OK")), self::USER, $this->secret);
  }

  #[Test]
  public function create_digest_authorization() {
    Assert::equals(
      $this->digest,
      Authorizations::fromResponse($this->newConnection()->get('/'), self::USER, $this->secret)
    );
  }

  #[Test, Expect(MethodNotImplementedException::class)]
  public function only_md5_algorithm_supported() {
    $http= $this->newConnection();
    $http->setResponse(new HttpResponse(new MemoryInputStream(
      "HTTP/1.0 401 Unauthorized\r\n".
      'WWW-Authenticate: Digest realm="testrealm@host.com", '.
      'qop="auth,auth-int", '.
      'nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093", '.
      'opaque="5ccc069c403ebaf9f0171e9517f40e41", '.
      'algorithm="sha1"'."\r\n"
    )));

    Authorizations::fromResponse($http->get('/'), self::USER, $this->secret);
  }

  #[Test]
  public function calculate_digest() {
    Assert::equals(
      '6629fae49393a05397450978507c4ef1',
      $this->digest->hashFor('GET', '/dir/index.html')
    );
  }

  #[Test]
  public function sign_adds_authorization_header() {
    $req= new HttpRequest(new URL('http://example.com:80/dir/index.html'));
    $this->digest->sign($req);

    Assert::equals(
      "GET /dir/index.html HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: example.com:80\r\n".
      'Authorization: Digest username="Mufasa", realm="testrealm@host.com", nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093", uri="/dir/index.html", qop="auth", nc=00000001, cnonce="0a4f113b", response="6629fae49393a05397450978507c4ef1", opaque="5ccc069c403ebaf9f0171e9517f40e41"'.
      "\r\n\r\n",
      $req->getHeaderString()
    );
  }

  #[Test]
  public function challenge_digest() {
    $http= $this->newConnection();
    $req= new HttpRequest(new URL('http://example.com:80/dir/index.html'));
    $res= $http->send($req);

    if (HttpConstants::STATUS_AUTHORIZATION_REQUIRED === $res->getStatusCode()) {
      $digest= Authorizations::fromResponse($res, self::USER, $this->secret);
      $digest->cnonce(self::CNONCE); // Hardcode client nconce, so hashes will be static for the tests
      $req= $http->create($req);
      $digest->sign($req);
    }

    Assert::equals(
      "GET /dir/index.html HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: example.com:80\r\n".
      'Authorization: Digest username="Mufasa", realm="testrealm@host.com", nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093", uri="/dir/index.html", qop="auth", nc=00000001, cnonce="0a4f113b", response="6629fae49393a05397450978507c4ef1", opaque="5ccc069c403ebaf9f0171e9517f40e41"'.
      "\r\n\r\n",
      $req->getHeaderString()
    );
  }

  #[Test]
  public function digest_hashes_path() {
    Assert::notEquals(
      $this->digest->hashFor('GET', '/dir/index.html'),
      $this->digest->hashFor('GET', '/other/index.html')
    );
  }

  #[Test]
  public function digest_hashes_querystring() {
    Assert::notEquals(
      $this->digest->hashFor('GET', '/dir/index.html?one'),
      $this->digest->hashFor('GET', '/dir/index.html?two')
    );
  }

  #[Test]
  public function opaque_is_optional() {
    $digest= DigestAuthorization::fromChallenge(
      'WWW-Authenticate: Digest realm="testrealm@host.com", '.
      'qop="auth,auth-int", '.
      'nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093"',
      self::USER,
      $this->secret
    );
    $digest->cnonce(self::CNONCE); // Hardcode client nconce, so hashes will be static for the tests

    Assert::equals(
      sprintf('Digest username="Mufasa", realm="testrealm@host.com", nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093", uri="/", qop="auth", nc=00000001, cnonce="%s", response="%s"',
        self::CNONCE, $digest->hashFor('GET', '/')
      ),
      $digest->getValueRepresentation('GET', '/')
    );
  }

  #[Test]
  public function md5_is_supported_algorithm() {
    $digest= DigestAuthorization::fromChallenge(
      'WWW-Authenticate: Digest realm="testrealm@host.com", '.
      'qop="auth,auth-int", '.
      'nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093", '.
      'algorithm="md5"',
      self::USER,
      $this->secret
    );
  }


  #[Test, Expect(MethodNotImplementedException::class)]
  public function only_md5_is_supported_algorithm() {
    $digest= DigestAuthorization::fromChallenge(
      'WWW-Authenticate: Digest realm="testrealm@host.com", '.
      'qop="auth,auth-int", '.
      'nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093", '.
      'algorithm="sha1"',
      self::USER,
      $this->secret
    );
  }

  #[Test]
  public function sign_includes_request_params() {
    $request= new HttpRequest(new URL('http://example.com/foo'));
    $request->setParameter('param', 'value');

    $this->digest->sign($request);
    $this->assertStringContains(
      'uri="/foo?param=value", ',
      $request->getHeaderString()
    );
  }

  #[Test]
  public function sign_includes_url_params() {
    $request= new HttpRequest(new URL('http://example.com/foo'));
    $request->getUrl()->setParam('param', 'value');

    $this->digest->sign($request);
    $this->assertStringContains(
      'uri="/foo?param=value", ',
      $request->getHeaderString()
    );
  }

  #[Test]
  public function sign_merges_url_params() {
    $request= new HttpRequest(new URL('http://example.com/foo'));
    $request->setParameter('param', 'value');
    $request->getUrl()->setParam('foo', 'bar');

    $this->digest->sign($request);
    $this->assertStringContains(
      'uri="/foo?param=value&foo=bar", ',
      $request->getHeaderString()
    );
  }

  #[Test]
  public function sign_merges_url_params_but_parameters_are_unique() {
    $request= new HttpRequest(new URL('http://example.com/foo'));
    $request->setParameter('param', 'value');
    $request->getUrl()->setParam('param', 'bar');

    $this->digest->sign($request);
    $this->assertStringContains(
      'uri="/foo?param=bar", ',
      $request->getHeaderString()
    );
  }
}