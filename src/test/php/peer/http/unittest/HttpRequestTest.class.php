<?php namespace peer\http\unittest;

use peer\URL;
use peer\http\RequestData;
use peer\http\FormRequestData;
use peer\http\FileUpload;
use peer\http\FormData;
use peer\http\HttpRequest;
use peer\http\HttpConstants;
use io\streams\MemoryInputStream;

/**
 * TestCase for HTTP request construction
 *
 * @see   xp://peer.http.HttpRequest
 * @see   https://github.com/xp-framework/xp-framework/issues/335
 */
class HttpRequestTest extends \unittest\TestCase {

  #[@test]
  public function get() {
    $r= new HttpRequest(new URL('http://example.com'));
    $r->setMethod(HttpConstants::GET);
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test, @values([80, 8080])]
  public function get_url_with_non_port($port) {
    $r= new HttpRequest(new URL('http://example.com:'.$port));
    $r->setMethod(HttpConstants::GET);
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com:".$port."\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  } 

  #[@test]
  public function get_url_with_path() {
    $r= new HttpRequest(new URL('http://example.com/path/to/images/index.html'));
    $r->setMethod(HttpConstants::GET);
    $this->assertEquals(
      "GET /path/to/images/index.html HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function basic_auth_supported_by_default() {
    $r= new HttpRequest(new URL('http://user:pass@example.com/'));
    $r->setMethod(HttpConstants::GET);
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nAuthorization: Basic dXNlcjpwYXNz\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function get_url_with_file_only_path() {
    $r= new HttpRequest(new URL('http://example.com/index.html'));
    $r->setMethod(HttpConstants::GET);
    $this->assertEquals(
      "GET /index.html HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function get_url_with_empty_parameters() {
    $r= new HttpRequest(new URL('http://example.com/?'));
    $r->setMethod(HttpConstants::GET);
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test, @values(['a=b', 'a=b&c=d', 'data[color]=green&data[size]=S'])]
  public function get_url_with_parameters_via_constructor($params) {
    $r= new HttpRequest(new URL('http://example.com/?'.$params));
    $r->setMethod(HttpConstants::GET);
    $this->assertEquals(
      "GET /?".$params." HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test, @values(['a=b', 'a=b&c=d', 'data[color]=green&data[size]=S'])]
  public function get_url_with_parameters_via_setParameters($params) {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters($params);
    $this->assertEquals(
      "GET /?".$params." HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function get_url_with_empty_array_parameters() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters(array());
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test, @values([[['a' => 'b'], 'a=b'], [['a' => 'b', 'c' => 'd'], 'a=b&c=d']])]
  public function get_url_with_array_parameters_via_setParameters($input, $representation) {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters($input);
    $this->assertEquals(
      "GET /?".$representation." HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function get_url_with_array_parameters_via_url() {
    $r= new HttpRequest(new URL('http://example.com/?data[color]=green&data[size]=S'));
    $r->setMethod(HttpConstants::GET);
    $this->assertEquals(
      "GET /?data[color]=green&data[size]=S HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function post_url_with_FormRequestData() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::POST);
    $formData= new FormRequestData([
      new FormData('key', 'value'),
      new FormData('xml', '<foo/>', 'text/xml')
    ]);
    $r->withBody($formData);

    // Fetch randomly generated boundary
    $boundary= $formData->boundary();
    $this->assertEquals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n".
      "Content-Type: multipart/form-data; boundary=".$boundary."\r\nContent-Length: 265\r\n\r\n".
      "--".$boundary."\r\nContent-Disposition: form-data; name=\"key\"\r\n".
      "\r\nvalue\r\n".
      "--".$boundary."\r\n".
      "Content-Disposition: form-data; name=\"xml\"\r\n".
      "Content-Type: text/xml\r\n".
      "\r\n<foo/>\r\n".
      "--".$boundary."--\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function post_url_with_FileUpload_parameters() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::POST);
    $formData= new FormRequestData([
      new FileUpload('file', 'image.jpeg', new \io\streams\MemoryInputStream('JFIF...'), 'image/jpeg'),
      new FileUpload('file', 'attach.txt', new \io\streams\MemoryInputStream('Test'), 'text/plain')
    ]);
    $r->setParameters($formData);

    // Fetch randomly generated boundary
    $boundary= $formData->boundary();
    $this->assertEquals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n".
      "Content-Type: multipart/form-data; boundary=".$boundary."\r\nContent-Length: 379\r\n\r\n".
      "--".$boundary."\r\nContent-Disposition: form-data; name=\"file\"; filename=\"image.jpeg\"\r\nContent-Type: image/jpeg\r\nContent-Length: 7\r\n".
      "\r\nJFIF...\r\n".
      "--".$boundary."\r\n".
      "Content-Disposition: form-data; name=\"file\"; filename=\"attach.txt\"\r\nContent-Type: text/plain\r\nContent-Length: 4\r\n".
      "\r\nTest\r\n".
      "--".$boundary."--\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test, @values([['a=b'], [['a' => 'b']]])]
  public function get_url_with_parameters_from_constructor_and_setParameters($params) {
    $r= new HttpRequest(new URL('http://example.com/?a=b'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters($params);
    $this->assertEquals(
      "GET /?a=b HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function get_url_with_map_parameter() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters(['params' => ['target' => 'home', 'ssl' => 'true']]);
    $this->assertEquals(
      "GET /?params[target]=home&params[ssl]=true HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function getUrl_returns_url_passed_to_constructor() {
    $url= new URL('http://example.com/');
    $r= new HttpRequest($url);
    $this->assertEquals($url, $r->getUrl());
  }

  #[@test]
  public function url_accessors() {
    $url= new URL('http://example.com/');
    $r= new HttpRequest();
    $r->setUrl($url);
    $this->assertEquals($url, $r->getUrl());
  }

  #[@test]
  public function setting_target_changes_url() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setTarget('/test');
    $this->assertEquals(new URL('http://example.com/test'), $r->getUrl());
  }

  #[@test]
  public function post() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::POST);
    $r->withBody('a=b&c=d');
    $this->assertEquals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n".
      "Content-Length: 7\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n".
      "a=b&c=d",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test, @values([
  #  ['data[color]=green&data[size]=S'],
  #  [['data' => ['color' => 'green', 'size' => 'S']]]
  #])]
  public function post_url_with_map_parameter($params) {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->withBody($params);
    $r->setMethod(HttpConstants::POST);
    $this->assertEquals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n".
      "Content-Length: 30\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n".
      "data[color]=green&data[size]=S",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function put() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::PUT);
    $r->setParameters('a=b&c=d');
    $this->assertEquals(
      "PUT /?a=b&c=d HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function trace() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::TRACE);
    $r->setParameters('a=b&c=d');
    $this->assertEquals(
      "TRACE /?a=b&c=d HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function head() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::HEAD);
    $r->setParameters('a=b&c=d');
    $this->assertEquals(
      "HEAD /?a=b&c=d HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function delete() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::DELETE);
    $r->setParameters('a=b&c=d');
    $this->assertEquals(
      "DELETE /?a=b&c=d HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function options() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::OPTIONS);
    $r->setParameters('a=b&c=d');
    $this->assertEquals(
      "OPTIONS /?a=b&c=d HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function with_custom_header() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setHeader('X-Binford', 6100);
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function with_custom_header_object() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setHeader('X-Binford', new \peer\Header('X-Binford', 6100));
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function with_custom_header_list() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setHeader('X-Binford', array(6100, 'More Power'));
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\nX-Binford: More Power\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function with_custom_headers() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->addHeaders(array('X-Binford' => 6100));
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function with_custom_headers_as_map() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->addHeaders(array('X-Binford' => new \peer\Header('X-Binford', 6100)));
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function with_custom_header_objects() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->addHeaders(array(new \peer\Header('X-Binford', 6100)));
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function with_custom_headers_list() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->addHeaders(array('X-Binford' => array(6100, 'Even more power')));
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 6100\r\nX-Binford: Even more power\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function with_duplicate_header() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setHeader('X-Binford', 6100);
    $r->setHeader('X-Binford', 61000);
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nX-Binford: 61000\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function header_string() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::GET);
    $r->setParameters('a=b');
    $this->assertEquals(
      "GET /?a=b HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test, @values([
  #  ['Test'],
  #  [new RequestData('Test')],
  #  [new MemoryInputStream('Test')]
  #])]
  public function with_body($body) {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::POST);
    $r->withBody($body);
    $this->assertEquals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nContent-Length: 4\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\nTest",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function with_empty_body() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::POST);
    $r->withBody(new RequestData(''));
    $this->assertEquals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nContent-Length: 0\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }

  #[@test]
  public function using_non_seekable_stream() {
    $r= new HttpRequest(new URL('http://example.com/'));
    $r->setMethod(HttpConstants::POST);
    $r->withBody(newinstance('io.streams.InputStream', [], [
      'available' => function() { return false; },
      'read'      => function($bytes= 8192) { /* Empty */ },
      'close'     => function() { /* Empty */ }
    ]));
    $this->assertEquals(
      "POST / HTTP/1.1\r\nConnection: close\r\nHost: example.com\r\nContent-Transfer-Encoding: chunked\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n",
      $r->write(new ToString())->bytes()
    );
  }
}
