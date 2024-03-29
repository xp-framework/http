<?php namespace peer\http\unittest;

use io\streams\MemoryInputStream;
use lang\FormatException;
use peer\http\HttpResponse;
use test\Assert;
use test\{Expect, Test};

/**
 * TestCase for HTTP responses
 *
 * @see   xp://peer.http.HttpResponse
 */
class HttpResponseTest {

  /**
   * Get a response with the specified headers and body
   *
   * @param   string[] headers
   * @param   string body default ''
   * @return  peer.http.HttpResponse
   */
  protected function newResponse(array $headers, $body= '') {
    return new HttpResponse(new MemoryInputStream(implode("\r\n", $headers)."\r\n\r\n".$body));
  }

  #[Test]
  public function errorDocument() {
    $body= '<h1>File not found</h1>';
    $response= $this->newResponse(['HTTP/1.0 404 OK', 'Content-Length: 23', 'Content-Type: text/html'], $body);
    Assert::equals(404, $response->statusCode());
    Assert::equals(['23'], $response->header('Content-Length'));
    Assert::equals(['text/html'], $response->header('Content-Type'));
    Assert::equals($body, $response->readData());
  }

  #[Test]
  public function emptyDocument() {
    $response= $this->newResponse(['HTTP/1.0 204 No content']);
    Assert::equals(204, $response->statusCode());
  }

  #[Test]
  public function chunkedDocument() {
    $body= '<h1>File not found</h1>';
    $response= $this->newResponse(['HTTP/1.0 404 OK', 'Transfer-Encoding: chunked'], "17\r\n".$body."\r\n0\r\n");
    Assert::equals(404, $response->statusCode());
    Assert::equals(['chunked'], $response->header('Transfer-Encoding'));
    Assert::equals($body, $response->readData());
  }

  #[Test]
  public function multipleChunkedDocument() {
    $response= $this->newResponse(
      ['HTTP/1.0 404 OK', 'Transfer-Encoding: chunked'],
      "17\r\n<h1>File not found</h1>\r\n13\r\nDid my best, sorry.\r\n0\r\n"
    );
    Assert::equals(404, $response->statusCode());
    Assert::equals(['chunked'], $response->header('Transfer-Encoding'));
    
    // Read data & test body contents
    $buffer= ''; while ($l= $response->readData()) { $buffer.= $l; }
    Assert::equals('<h1>File not found</h1>Did my best, sorry.', $buffer);
  }

  #[Test]
  public function httpContinue() {
    $response= $this->newResponse(['HTTP/1.0 100 Continue', '', 'HTTP/1.0 200 OK', 'Content-Length: 4'], 'Test');
    Assert::equals(200, $response->statusCode());
    Assert::equals(['4'], $response->header('Content-Length'));
    Assert::equals('Test', $response->readData());
  }
  
  #[Test]
  public function statusCodeWithMessage() {
    $response= $this->newResponse(['HTTP/1.1 404 Not Found'], 'File Not Found');
    Assert::equals(404, $response->statusCode());
    Assert::equals('Not Found', $response->message());
    Assert::equals('File Not Found', $response->readData());
  }
  
  #[Test]
  public function statusCodeWithoutMessage() {
    $response= $this->newResponse(['HTTP/1.1 404'], 'File Not Found');
    Assert::equals(404, $response->statusCode());
    Assert::equals('', $response->message());
    Assert::equals('File Not Found', $response->readData());
  }

  #[Test, Expect(FormatException::class)]
  public function incorrectProtocol() {
    $this->newResponse(['* OK IMAP server ready H mimap20 68140']);
  }

  #[Test]
  public function getHeader() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Binford: 6100', 'Content-Type: text/html']);
    Assert::equals('6100', $response->getHeader('X-Binford'));
    Assert::equals('text/html', $response->getHeader('Content-Type'));
  }

  #[Test]
  public function getHeaderIsCaseInsensitive() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Binford: 6100', 'Content-Type: text/html']);
    Assert::equals('6100', $response->getHeader('x-binford'), 'all-lowercase');
    Assert::equals('text/html', $response->getHeader('CONTENT-TYPE'), 'all-uppercase');
  }

  #[Test]
  public function nonExistantGetHeader() {
    $response= $this->newResponse(['HTTP/1.0 204 No Content']);
    Assert::null($response->getHeader('Via'));
  }

  #[Test]
  public function multipleCookiesInGetHeader() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'Set-Cookie: color=green; path=/', 'Set-Cookie: make=example; path=/']);
    Assert::equals(
      'make=example; path=/',
      $response->getHeader('Set-Cookie')
    );
  }

  #[Test]
  public function getHeaders() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Binford: 6100', 'Content-Type: text/html']);
    Assert::equals(
      ['X-Binford' => '6100', 'Content-Type' => 'text/html'],
      $response->getHeaders()
    );
  }

  #[Test]
  public function emptyGetHeaders() {
    $response= $this->newResponse(['HTTP/1.0 204 No Content']);
    Assert::equals(
      [],
      $response->getHeaders()
    );
  }

  #[Test]
  public function multipleCookiesInGetHeaders() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'Set-Cookie: color=green; path=/', 'Set-Cookie: make=example; path=/']);
    Assert::equals(
      ['Set-Cookie' => 'make=example; path=/'],
      $response->getHeaders('Set-Cookie')
    );
  }

  #[Test]
  public function header() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Binford: 6100', 'Content-Type: text/html']);
    Assert::equals(['6100'], $response->header('X-Binford'));
    Assert::equals(['text/html'], $response->header('Content-Type'));
  }

  #[Test]
  public function headerIsCaseInsensitive() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Binford: 6100', 'Content-Type: text/html']);
    Assert::equals(['6100'], $response->header('x-binford'), 'all-lowercase');
    Assert::equals(['text/html'], $response->header('CONTENT-TYPE'), 'all-uppercase');
  }

  #[Test]
  public function nonExistantHeader() {
    $response= $this->newResponse(['HTTP/1.0 204 No Content']);
    Assert::null($response->header('Via'));
  }

  #[Test]
  public function multipleCookiesInHeader() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'Set-Cookie: color=green; path=/', 'Set-Cookie: make=example; path=/']);
    Assert::equals(
      ['color=green; path=/', 'make=example; path=/'],
      $response->header('Set-Cookie')
    );
  }

  #[Test]
  public function multipleCookiesInHeaders() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'Set-Cookie: color=green; path=/', 'Set-Cookie: make=example; path=/']);
    Assert::equals(
      ['Set-Cookie' => ['color=green; path=/', 'make=example; path=/']],
      $response->headers()
    );
  }

  #[Test]
  public function headers() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Binford: 6100', 'Content-Type: text/html']);
    Assert::equals(
      ['X-Binford' => ['6100'], 'Content-Type' => ['text/html']],
      $response->headers()
    );
  }

  #[Test]
  public function emptyHeaders() {
    $response= $this->newResponse(['HTTP/1.0 204 No Content']);
    Assert::equals(
      [],
      $response->headers()
    );
  }

  #[Test]
  public function multipleHeadersWithDifferentCasing() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Example: K', 'x-example: V']);
    Assert::equals(
      ['X-Example' => ['K', 'V']],
      $response->headers()
    );
  }

  #[Test]
  public function multipleHeaderWithDifferentCasing() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Example: K', 'x-example: V']);
    Assert::equals(
      ['K', 'V'],
      $response->header('X-Example')
    );
  }

  #[Test]
  public function headerString() {
    $response= $this->newResponse(['HTTP/1.1 200 OK', 'Content-Type: application/json', 'Content-Length: 0']);
    Assert::equals(
      "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: 0\r\n\r\n",
      $response->getHeaderString()
    );
  }

  #[Test]
  public function headerStringDoesNotIncludeContent() {
    $response= $this->newResponse(['HTTP/1.1 200 OK', 'Content-Type: application/json', 'Content-Length: 21'], '{ "hello" : "world" }');
    Assert::equals(
      "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: 21\r\n\r\n",
      $response->getHeaderString()
    );
  }

  #[Test]
  public function headerWithoutValue() {
    $body= '.';
    $response= $this->newResponse(['HTTP/1.1 401 Unauthorized', 'Cache-Control: '], $body);
    Assert::equals(401, $response->statusCode());
    Assert::equals([null], $response->header('Cache-Control'));
    Assert::equals($body, $response->readData());
  }
}