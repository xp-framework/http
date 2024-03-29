<?php namespace peer\http\unittest;

use peer\URL;
use peer\http\{FileUpload, FormData, FormRequestData, Header, HttpConstants, HttpRequest, RequestData};
use test\Assert;
use test\{Test, Values};

/**
 * TestCase for HTTP request construction
 *
 * @see   xp://peer.http.HttpRequest
 * @see   https://github.com/xp-framework/xp-framework/issues/335
 */
class HttpRequestTest {

  #[Test]
  public function get() {
    $r= new HttpRequest(new URL('http://example.com'));
    $r->setMethod(HttpConstants::GET);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test, Values([80, 8080])]
  public function get_url_with_non_port($port) {
    $r= new HttpRequest(new URL('http://example.com:'.$port));
    $r->setMethod(HttpConstants::GET);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com:".$port."\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function get_url_with_path() {
    $r= new HttpRequest(new URL('http://example.com/path/to/images/index.html'));
    $r->setMethod(HttpConstants::GET);
    Assert::equals(
      "GET /path/to/images/index.html HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function basic_auth_supported_by_default() {
    $r= new HttpRequest(new URL('http://user:pass@example.com/'));
    $r->setMethod(HttpConstants::GET);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nAuthorization: Basic dXNlcjpwYXNz\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function get_url_with_file_only_path() {
    $r= new HttpRequest(new URL('http://example.com/index.html'));
    $r->setMethod(HttpConstants::GET);
    Assert::equals(
      "GET /index.html HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function get_url_with_empty_parameters() {
    $r= new HttpRequest(new URL('http://example.com/?'));
    $r->setMethod(HttpConstants::GET);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test, Values(['a=b', 'a=b&c=d', 'data[color]=green&data[size]=S'])]
  public function get_url_with_parameters_via_constructor($params) {
    $r= new HttpRequest(new URL('http://example.com/?'.$params));
    $r->setMethod(HttpConstants::GET);
    Assert::equals(
      "GET /?".$params." HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test, Values(['a=b', 'a=b&c=d', 'data[color]=green&data[size]=S'])]
  public function get_url_with_parameters_via_setParameters($params) {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters($params);
    Assert::equals(
      "GET /?".$params." HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function get_url_with_empty_array_parameters() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters([]);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test, Values([[['a' => 'b'], 'a=b'], [['a' => 'b', 'c' => 'd'], 'a=b&c=d']])]
  public function get_url_with_array_parameters_via_setParameters($input, $representation) {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters($input);
    Assert::equals(
      "GET /?".$representation." HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function get_url_with_array_parameters_via_url() {
    $r= new HttpRequest(new URL('http://example.com/?data[color]=green&data[size]=S'));
    $r->setMethod(HttpConstants::GET);
    Assert::equals(
      "GET /?data[color]=green&data[size]=S HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function post_url_with_RequestData_parameters() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::POST);
    $r->setParameters(new FormRequestData([
      new FormData('key', 'value'),
      new FormData('xml', '<foo/>', 'text/xml')
    ]));

    // Fetch randomly generated boundary
    $boundary= $r->parameters->getBoundary();
    Assert::equals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n".
      "Content-Type: multipart/form-data; boundary=".$boundary."\r\nContent-Length: 265\r\n\r\n".
      "--".$boundary."\r\nContent-Disposition: form-data; name=\"key\"\r\n".
      "\r\nvalue\r\n".
      "--".$boundary."\r\n".
      "Content-Disposition: form-data; name=\"xml\"\r\n".
      "Content-Type: text/xml\r\n".
      "\r\n<foo/>\r\n".
      "--".$boundary."--\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function post_url_with_FileUpload_parameters() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::POST);
    $r->setParameters(new FormRequestData([
      new FileUpload('file', 'image.jpeg', new \io\streams\MemoryInputStream('JFIF...'), 'image/jpeg'),
      new FileUpload('file', 'attach.txt', new \io\streams\MemoryInputStream('Test'), 'text/plain')
    ]));

    // Fetch randomly generated boundary
    $boundary= $r->parameters->getBoundary();
    Assert::equals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n".
      "Content-Type: multipart/form-data; boundary=".$boundary."\r\nContent-Length: 379\r\n\r\n".
      "--".$boundary."\r\nContent-Disposition: form-data; name=\"file\"; filename=\"image.jpeg\"\r\nContent-Type: image/jpeg\r\nContent-Length: 7\r\n".
      "\r\nJFIF...\r\n".
      "--".$boundary."\r\n".
      "Content-Disposition: form-data; name=\"file\"; filename=\"attach.txt\"\r\nContent-Type: text/plain\r\nContent-Length: 4\r\n".
      "\r\nTest\r\n".
      "--".$boundary."--\r\n",
      $r->getRequestString()
    );
  }

  #[Test, Values([['a=b'], [['a' => 'b']]])]
  public function get_url_with_parameters_from_constructor_and_setParameters($params) {
    $r= new HttpRequest(new URL('http://example.com/?a=b'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters($params);
    Assert::equals(
      "GET /?a=b HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function get_url_with_map_parameter() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters(['params' => ['target' => 'home', 'ssl' => 'true']]);
    Assert::equals(
      "GET /?params[target]=home&params[ssl]=true HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function getUrl_returns_url_passed_to_constructor() {
    $url= new URL('http://example.com/');
    $r= new HttpRequest($url);
    Assert::equals($url, $r->getUrl());
  }

  #[Test]
  public function url_accessors() {
    $url= new URL('http://example.com/');
    $r= new HttpRequest();
    $r->setUrl($url);
    Assert::equals($url, $r->getUrl());
  }

  #[Test]
  public function setting_target_changes_url() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setTarget('/test');
    Assert::equals(new URL('http://example.com/test'), $r->getUrl());
  }

  #[Test]
  public function post() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::POST);
    $r->setParameters('a=b&c=d');
    Assert::equals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n".
      "Content-Length: 7\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n".
      "a=b&c=d",
      $r->getRequestString()
    );
  }

  #[Test, Values([['data[color]=green&data[size]=S'], [['data' => ['color' => 'green', 'size' => 'S']]]])]
  public function post_url_with_map_parameter($params) {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setParameters($params);
    $r->setMethod(HttpConstants::POST);
    Assert::equals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n".
      "Content-Length: 30\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n".
      "data[color]=green&data[size]=S",
      $r->getRequestString()
    );
  }

  #[Test]
  public function put() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::PUT);
    $r->setParameters('a=b&c=d');
    Assert::equals(
      "PUT / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n".
      "Content-Length: 7\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n".
      "a=b&c=d",
      $r->getRequestString()
    );
  }

  #[Test]
  public function trace() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::TRACE);
    $r->setParameters('a=b&c=d');
    Assert::equals(
      "TRACE / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n".
      "Content-Length: 7\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n".
      "a=b&c=d",
      $r->getRequestString()
    );
  }

  #[Test]
  public function head() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::HEAD);
    $r->setParameters('a=b&c=d');
    Assert::equals(
      "HEAD /?a=b&c=d HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function delete() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::DELETE);
    $r->setParameters('a=b&c=d');
    Assert::equals(
      "DELETE /?a=b&c=d HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function options() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::OPTIONS);
    $r->setParameters('a=b&c=d');
    Assert::equals(
      "OPTIONS /?a=b&c=d HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function with_custom_header() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setHeader('X-Binford', 6100);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function with_custom_header_object() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setHeader('X-Binford', new Header('X-Binford', 6100));
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function with_custom_header_list() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setHeader('X-Binford', [6100, 'More Power']);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\nX-Binford: More Power\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function with_custom_headers() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->addHeaders(['X-Binford' => 6100]);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function with_custom_headers_as_map() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->addHeaders(['X-Binford' => new Header('X-Binford', 6100)]);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function with_custom_header_objects() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->addHeaders([new Header('X-Binford', 6100)]);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function with_custom_headers_list() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->addHeaders(['X-Binford' => [6100, 'Even more power']]);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\nX-Binford: Even more power\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function with_duplicate_header() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setHeader('X-Binford', 6100);
    $r->setHeader('X-Binford', 61000);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 61000\r\n\r\n",
      $r->getRequestString()
    );
  }

  #[Test]
  public function header_string() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters('a=b');
    Assert::equals(
      "GET /?a=b HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getHeaderString()
    );
  }

  #[Test]
  public function header_string_does_not_include_content() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::POST);
    $r->setParameters('a=b');
    Assert::equals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nContent-Length: 3\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n",
      $r->getHeaderString()
    );
  }

  #[Test]
  public function with_empty_post_body() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::POST);
    $r->setParameters('');
    Assert::equals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nContent-Length: 0\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n",
      $r->getHeaderString()
    );
  }

  #[Test]
  public function post_with_1byte_body() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::POST);
    $r->setParameters(new RequestData('1'));
    Assert::equals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nContent-Length: 1\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n",
      $r->getHeaderString()
    );
  }

  #[Test]
  public function delete_with_1byte_body() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::DELETE);
    $r->setParameters(new RequestData('1'));
    Assert::equals(
      "DELETE / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nContent-Length: 1\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n",
      $r->getHeaderString()
    );
  }

 /**
  * Test HTTP GET - parameters via setParameters(array<string, string>)
  * BUT array key contains spaces!
  *
  * Example of assoc-array:
  *  $test= [
  *    '10 EUR' => '100 teststeine',
  *    '7.14 TRY' => '200 teststeine'
  *  ];
  */
  #[Test]
  public function get_url_with_array_params_spaced_key() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters(['10 EUR' => 'test 123']);
    Assert::equals(
      "GET /?10+EUR=test+123 HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }

  /**
   * Test HTTP GET - parameters via setParameters(array<array<string, string>>)
   * BUT array contains array which might have keys containing spaces!
   *
   * Example of assoc-array:
   *  $test= [
   *    'test' => [
   *      '10 EUR' => '100 teststeine',
   *      '7.14 TRY' => '200 teststeine'
   *    ]
   *  ];
   */
   #[Test]
   public function get_url_with_assoc_array_containing_assoc_array() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters(['test' => ['10 EUR' => 'test 123']]);
    Assert::equals(
      "GET /?test[10+EUR]=test+123 HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->getRequestString()
    );
  }
}