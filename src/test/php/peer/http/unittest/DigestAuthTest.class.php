<?php namespace peer\http\unittest;

use peer\URL;
use peer\http\HttpRequest;
use peer\http\HttpResponse;
use peer\http\HttpConstants;
use peer\http\Authorizations;
use peer\http\DigestAuthorization;
use security\SecureString;
use io\streams\MemoryInputStream;
use lang\MethodNotImplementedException;

class DigestAuthTest extends \unittest\TestCase {
  private $http= null;
  private $digest= null;

  public function setUp() {
    $this->http= new MockHttpConnection(new URL('http://example.com:80/path/of/file'));
    $this->digest= new DigestAuthorization(
      'testrealm@host.com',
      'auth,auth-int',
      'dcd98b7102dd2f0e8b11d0f600bfb0c093',
      '5ccc069c403ebaf9f0171e9517f40e41'
    );
    $this->digest->cnonce('0a4f113b');
    $this->digest->username('Mufasa');
    $this->digest->password(new SecureString('Circle Of Life'));

  }

  #[@test]
  public function server_indicates_digest_auth() {
    $this->http->setResponse(new HttpResponse(new \io\streams\MemoryInputStream(
      "HTTP/1.0 401 Unauthorized\r\n".
      'WWW-Authenticate: Digest realm="testrealm@host.com", '.
      'qop="auth,auth-int", '.
      'nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093", '.
      'opaque="5ccc069c403ebaf9f0171e9517f40e41"'."\r\n"
    )));

    $this->assertEquals(HttpConstants::STATUS_AUTHORIZATION_REQUIRED, $this->http->get('/')->getStatusCode());
  }

  #[@test, @expect('lang.IllegalStateException')]
  public function no_auth_when_not_indicated() {
    Authorizations::fromResponse(new HttpResponse(new MemoryInputStream("HTTP/1.0 200 OK")), 'user', new SecureString('pass'));
  }

  #[@test]
  public function create_digest_authorization() {
    $this->http->setResponse(new HttpResponse(new MemoryInputStream(
      "HTTP/1.0 401 Unauthorized\r\n".
      'WWW-Authenticate: Digest realm="testrealm@host.com", '.
      'qop="auth,auth-int", '.
      'nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093", '.
      'opaque="5ccc069c403ebaf9f0171e9517f40e41"'."\r\n"
    )));

    $this->assertEquals(
      $this->digest,
      Authorizations::fromResponse($this->http->get('/'), 'user', new SecureString('pass'))
    );
  }

  #[@test, @expect('lang.MethodNotImplementedException')]
  public function only_md5_algorithm_supported() {
    $this->http->setResponse(new HttpResponse(new MemoryInputStream(
      "HTTP/1.0 401 Unauthorized\r\n".
      'WWW-Authenticate: Digest realm="testrealm@host.com", '.
      'qop="auth,auth-int", '.
      'nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093", '.
      'opaque="5ccc069c403ebaf9f0171e9517f40e41", '.
      'algorithm="sha1"'."\r\n"
    )));

    Authorizations::fromResponse($this->http->get('/'), 'user', new SecureString('pass'));
  }

  #[@test]
  public function calculate_digest() {
    $req= new HttpRequest(new URL('http://example.com:80/dir/index.html'));
    $this->assertEquals(
      '6629fae49393a05397450978507c4ef1',
      $this->digest->responseFor($req)
    );
  }

  #[@test]
  public function attach_adds_authorization_header() {
    $req= new HttpRequest(new URL('http://example.com:80/dir/index.html'));
    $this->digest->authorize($req);

    $this->assertEquals(
      "GET /dir/index.html HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: example.com:80\r\n".
      'Authorization: Digest username="Mufasa", realm="testrealm@host.com", nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093", uri="/dir/index.html", qop=auth", nc=00000001, cnonce="0a4f113b", response="6629fae49393a05397450978507c4ef1", opaque="5ccc069c403ebaf9f0171e9517f40e41"'.
      "\r\n\r\n",
      $req->getHeaderString()
    );
  }
}