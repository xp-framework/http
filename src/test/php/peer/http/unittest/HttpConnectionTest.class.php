<?php namespace peer\http\unittest;

use peer\URL;
use peer\http\{BasicAuthorization, HttpConstants, HttpProxy, HttpRequest, RequestData};
use test\{Assert, Before, Test};
use util\log\layout\PatternLayout;
use util\log\{BufferedAppender, LogCategory, Traceable};

class HttpConnectionTest {
  private $fixture;

  #[Before]
  public function fixture() {
    $this->fixture= new MockHttpConnection(new URL('http://example.com:80/path/of/file'));
  }

  #[Test]
  public function get() {
    $this->fixture->get(['var1' => 1, 'var2' => 2]);
    Assert::equals(
      "GET /path/of/file?var1=1&var2=2 HTTP/1.1\r\nConnection: close\r\nHost: example.com:80\r\n\r\n",
      $this->fixture->lastRequest()
    );
  }
  
  #[Test]
  public function head() {
    $this->fixture->head(['var1' => 1, 'var2' => 2]);
    Assert::equals(
      "HEAD /path/of/file?var1=1&var2=2 HTTP/1.1\r\nConnection: close\r\nHost: example.com:80\r\n\r\n",
      $this->fixture->lastRequest()
    );
  }

  #[Test]
  public function post() {
    $this->fixture->post(['var1' => 1, 'var2' => 2]);
    Assert::equals(
      "POST /path/of/file HTTP/1.1\r\nConnection: close\r\nHost: example.com:80\r\nContent-Length: 13\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\nvar1=1&var2=2",
      $this->fixture->lastRequest()
    );
  }

  #[Test]
  public function put() {
    $this->fixture->put(new RequestData('THIS IS A DATA STRING'));
    Assert::equals(
      "PUT /path/of/file HTTP/1.1\r\nConnection: close\r\nHost: example.com:80\r\nContent-Length: 21\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\nTHIS IS A DATA STRING",
      $this->fixture->lastRequest()
    );
  }

  #[Test]
  public function patch() {
    $this->fixture->patch(new RequestData('THIS IS A DATA STRING'));
    Assert::equals(
      "PATCH /path/of/file HTTP/1.1\r\nConnection: close\r\nHost: example.com:80\r\nContent-Length: 21\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\nTHIS IS A DATA STRING",
      $this->fixture->lastRequest()
    );
  }

  #[Test]
  public function delete() {
    $this->fixture->delete(['var1' => 1, 'var2' => 2]);
    Assert::equals(
      "DELETE /path/of/file?var1=1&var2=2 HTTP/1.1\r\nConnection: close\r\nHost: example.com:80\r\n\r\n",
      $this->fixture->lastRequest()
    );
  }

  #[Test]
  public function options() {
    $this->fixture->options(['var1' => 1, 'var2' => 2]);
    Assert::equals(
      "OPTIONS /path/of/file?var1=1&var2=2 HTTP/1.1\r\nConnection: close\r\nHost: example.com:80\r\n\r\n",
      $this->fixture->lastRequest()
    );
  }

  #[Test]
  public function create_and_send() {
    with ($request= $this->fixture->create(new HttpRequest())); {
      $request->setMethod('PROPPATCH');   // Webdav
      $request->setTarget('/');
      $this->fixture->send($request);
    }
    Assert::equals(
      "PROPPATCH / HTTP/1.1\r\nConnection: close\r\nHost: example.com:80\r\nContent-Length: 0\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n",
      $this->fixture->lastRequest()
    );
  }

  #[Test]
  public function is_traceable() {
    Assert::instance(Traceable::class, $this->fixture);
  }

  #[Test]
  public function tracing() {
    $appender= (new BufferedAppender())->withLayout(new PatternLayout('%m'));
    $this->fixture->setTrace((new LogCategory('trace'))->withAppender($appender));
    $this->fixture->get();
    Assert::equals(
      ">>> GET /path/of/file HTTP/1.1\r\nConnection: close\r\nHost: example.com:80\r\n\r\n".
      "<<< HTTP/1.0 200 Testing OK\r\n\r\n",
      $appender->getBuffer()
    );
  }

  #[Test]
  public function changing_request_target_does_not_modify_connection_url() {
    $url= $this->fixture->getUrl();
    $this->fixture->create(new HttpRequest())->setTarget('/foo');
    Assert::notEquals('/foo', $url->getPath());
  }

  #[Test]
  public function can_force_direct_connection() {
    $this->fixture->setProxy(HttpProxy::NONE);
  }

  #[Test]
  public function can_add_authorization_as_header() {
    $req= $this->fixture->create(new HttpRequest());
    $req->setHeader('Authorization', new BasicAuthorization('user', 'pass'));

    Assert::equals(
      "GET /path/of/file HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: example.com:80\r\n".
      "Authorization: Basic dXNlcjpwYXNz\r\n\r\n",
      $req->getHeaderString()
    );
  }

  #[Test]
  public function can_add_authorization_as_header_in_get() {
    $this->fixture->get([], [new BasicAuthorization('user', 'pass')]);

    Assert::equals(
      "GET /path/of/file HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: example.com:80\r\n".
      "Authorization: Basic dXNlcjpwYXNz\r\n\r\n",
      $this->fixture->lastRequest()
    );
  }

  #[Test]
  public function can_add_authorization_within_url() {
    $conn= new MockHttpConnection('http://user:pass@example.com/');
    $conn->get();

    Assert::equals(
      "GET / HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Authorization: Basic dXNlcjpwYXNz\r\n".
      "Host: example.com\r\n\r\n",
      $conn->lastRequest()
    );
  }

  #[Test]
  public function open_transfer() {
    $request= $this->fixture->create(new HttpRequest());
    $request->setMethod('POST');
    $request->setTarget('/');
    $request->setHeader('Content-Length', 13);
    $request->setHeader('Content-Type', 'application/x-www-form-urlencoded');

    $transfer= $this->fixture->open($request);
    $transfer->write('var1=1&var2=2');
    $this->fixture->finish($transfer);

    Assert::equals(
      "POST / HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: example.com:80\r\n".
      "Content-Length: 13\r\n".
      "Content-Type: application/x-www-form-urlencoded\r\n".
      "\r\n".
      "var1=1&var2=2",
      $this->fixture->lastRequest()
    );
  }
}