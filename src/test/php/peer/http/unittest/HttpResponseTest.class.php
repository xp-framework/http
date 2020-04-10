<?php namespace peer\http\unittest;

use io\streams\MemoryInputStream;
use lang\FormatException;
use peer\http\HttpResponse;

/**
 * TestCase for HTTP responses
 *
 * @see   xp://peer.http.HttpResponse
 */
class HttpResponseTest extends \unittest\TestCase {

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

  #[@test]
  public function errorDocument() {
    $body= '<h1>File not found</h1>';
    $response= $this->newResponse(['HTTP/1.0 404 OK', 'Content-Length: 23', 'Content-Type: text/html'], $body);
    $this->assertEquals(404, $response->statusCode());
    $this->assertEquals(['23'], $response->header('Content-Length'));
    $this->assertEquals(['text/html'], $response->header('Content-Type'));
    $this->assertEquals($body, $response->readData());
  }

  #[@test]
  public function emptyDocument() {
    $response= $this->newResponse(['HTTP/1.0 204 No content']);
    $this->assertEquals(204, $response->statusCode());
  }

  #[@test]
  public function chunkedDocument() {
    $body= '<h1>File not found</h1>';
    $response= $this->newResponse(['HTTP/1.0 404 OK', 'Transfer-Encoding: chunked'], "17\r\n".$body."\r\n0\r\n");
    $this->assertEquals(404, $response->statusCode());
    $this->assertEquals(['chunked'], $response->header('Transfer-Encoding'));
    $this->assertEquals($body, $response->readData());
  }

  #[@test]
  public function multipleChunkedDocument() {
    $response= $this->newResponse(
      ['HTTP/1.0 404 OK', 'Transfer-Encoding: chunked'],
      "17\r\n<h1>File not found</h1>\r\n13\r\nDid my best, sorry.\r\n0\r\n"
    );
    $this->assertEquals(404, $response->statusCode());
    $this->assertEquals(['chunked'], $response->header('Transfer-Encoding'));
    
    // Read data & test body contents
    $buffer= ''; while ($l= $response->readData()) { $buffer.= $l; }
    $this->assertEquals('<h1>File not found</h1>Did my best, sorry.', $buffer);
  }

  #[@test]
  public function httpContinue() {
    $response= $this->newResponse(['HTTP/1.0 100 Continue', '', 'HTTP/1.0 200 OK', 'Content-Length: 4'], 'Test');
    $this->assertEquals(200, $response->statusCode());
    $this->assertEquals(['4'], $response->header('Content-Length'));
    $this->assertEquals('Test', $response->readData());
  }
  
  #[@test]
  public function statusCodeWithMessage() {
    $response= $this->newResponse(['HTTP/1.1 404 Not Found'], 'File Not Found');
    $this->assertEquals(404, $response->statusCode());
    $this->assertEquals('Not Found', $response->message());
    $this->assertEquals('File Not Found', $response->readData());
  }
  
  #[@test]
  public function statusCodeWithoutMessage() {
    $response= $this->newResponse(['HTTP/1.1 404'], 'File Not Found');
    $this->assertEquals(404, $response->statusCode());
    $this->assertEquals('', $response->message());
    $this->assertEquals('File Not Found', $response->readData());
  }

  #[@test, @expect(FormatException::class)]
  public function incorrectProtocol() {
    $this->newResponse(['* OK IMAP server ready H mimap20 68140']);
  }

  #[@test]
  public function getHeader() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Binford: 6100', 'Content-Type: text/html']);
    $this->assertEquals('6100', $response->getHeader('X-Binford'));
    $this->assertEquals('text/html', $response->getHeader('Content-Type'));
  }

  #[@test]
  public function getHeaderIsCaseInsensitive() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Binford: 6100', 'Content-Type: text/html']);
    $this->assertEquals('6100', $response->getHeader('x-binford'), 'all-lowercase');
    $this->assertEquals('text/html', $response->getHeader('CONTENT-TYPE'), 'all-uppercase');
  }

  #[@test]
  public function nonExistantGetHeader() {
    $response= $this->newResponse(['HTTP/1.0 204 No Content']);
    $this->assertNull($response->getHeader('Via'));
  }

  #[@test]
  public function multipleCookiesInGetHeader() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'Set-Cookie: color=green; path=/', 'Set-Cookie: make=example; path=/']);
    $this->assertEquals(
      'make=example; path=/',
      $response->getHeader('Set-Cookie')
    );
  }

  #[@test]
  public function getHeaders() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Binford: 6100', 'Content-Type: text/html']);
    $this->assertEquals(
      ['X-Binford' => '6100', 'Content-Type' => 'text/html'],
      $response->getHeaders()
    );
  }

  #[@test]
  public function emptyGetHeaders() {
    $response= $this->newResponse(['HTTP/1.0 204 No Content']);
    $this->assertEquals(
      [],
      $response->getHeaders()
    );
  }

  #[@test]
  public function multipleCookiesInGetHeaders() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'Set-Cookie: color=green; path=/', 'Set-Cookie: make=example; path=/']);
    $this->assertEquals(
      ['Set-Cookie' => 'make=example; path=/'],
      $response->getHeaders('Set-Cookie')
    );
  }

  #[@test]
  public function header() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Binford: 6100', 'Content-Type: text/html']);
    $this->assertEquals(['6100'], $response->header('X-Binford'));
    $this->assertEquals(['text/html'], $response->header('Content-Type'));
  }

  #[@test]
  public function headerIsCaseInsensitive() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Binford: 6100', 'Content-Type: text/html']);
    $this->assertEquals(['6100'], $response->header('x-binford'), 'all-lowercase');
    $this->assertEquals(['text/html'], $response->header('CONTENT-TYPE'), 'all-uppercase');
  }

  #[@test]
  public function nonExistantHeader() {
    $response= $this->newResponse(['HTTP/1.0 204 No Content']);
    $this->assertNull($response->header('Via'));
  }

  #[@test]
  public function multipleCookiesInHeader() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'Set-Cookie: color=green; path=/', 'Set-Cookie: make=example; path=/']);
    $this->assertEquals(
      ['color=green; path=/', 'make=example; path=/'],
      $response->header('Set-Cookie')
    );
  }

  #[@test]
  public function multipleCookiesInHeaders() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'Set-Cookie: color=green; path=/', 'Set-Cookie: make=example; path=/']);
    $this->assertEquals(
      ['Set-Cookie' => ['color=green; path=/', 'make=example; path=/']],
      $response->headers()
    );
  }

  #[@test]
  public function headers() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Binford: 6100', 'Content-Type: text/html']);
    $this->assertEquals(
      ['X-Binford' => ['6100'], 'Content-Type' => ['text/html']],
      $response->headers()
    );
  }

  #[@test]
  public function emptyHeaders() {
    $response= $this->newResponse(['HTTP/1.0 204 No Content']);
    $this->assertEquals(
      [],
      $response->headers()
    );
  }

  #[@test]
  public function multipleHeadersWithDifferentCasing() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Example: K', 'x-example: V']);
    $this->assertEquals(
      ['X-Example' => ['K', 'V']],
      $response->headers()
    );
  }

  #[@test]
  public function multipleHeaderWithDifferentCasing() {
    $response= $this->newResponse(['HTTP/1.0 200 OK', 'X-Example: K', 'x-example: V']);
    $this->assertEquals(
      ['K', 'V'],
      $response->header('X-Example')
    );
  }

  #[@test]
  public function headerString() {
    $response= $this->newResponse(['HTTP/1.1 200 OK', 'Content-Type: application/json', 'Content-Length: 0']);
    $this->assertEquals(
      "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: 0\r\n\r\n",
      $response->getHeaderString()
    );
  }

  #[@test]
  public function headerStringDoesNotIncludeContent() {
    $response= $this->newResponse(['HTTP/1.1 200 OK', 'Content-Type: application/json', 'Content-Length: 21'], '{ "hello" : "world" }');
    $this->assertEquals(
      "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: 21\r\n\r\n",
      $response->getHeaderString()
    );
  }

  #[@test]
  public function headerWithoutValue() {
    $body= '.';
    $response= $this->newResponse(['HTTP/1.1 401 Unauthorized', 'Cache-Control: '], $body);
    $this->assertEquals(401, $response->statusCode());
    $this->assertEquals([null], $response->header('Cache-Control'));
    $this->assertEquals($body, $response->readData());
  }
}