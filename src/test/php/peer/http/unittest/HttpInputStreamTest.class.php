<?php namespace peer\http\unittest;

use io\streams\MemoryInputStream;
use peer\http\HttpResponse;
use peer\http\HttpConstants;

/**
 * HTTP input stream tests
 *
 * @see   xp://peer.http.HttpInputStream
 */
class HttpInputStreamTest extends \unittest\TestCase {

  /**
   * Returns a HTTP response object
   *
   * @param   int status
   * @param   array<string, string> headers
   * @param   string body default ''
   * @return  peer.http.HttpResponse
   */
  protected function httpResponse($status, $headers, $body= '') {
    $response= 'HTTP/'.HttpConstants::VERSION_1_1.' '.$status." Test\r\n";
    foreach ($headers as $key => $val) {
      $response.= $key.': '.$val."\r\n";
    }
    $response.= "\r\n".$body;

    return new HttpResponse(new MemoryInputStream($response));
  }
  
  /**
   * Reads stream contents
   *
   * @param   io.streams.InputStream is
   * @return  string bytes
   */
  protected function readAll(\io\streams\InputStream $is) {
    for ($contents= ''; $is->available(); ) {
      $contents.= $is->read();
    }
    $is->close();
    return $contents;
  }
  
  /**
   * Assertion helper
   *
   * @param   string data
   * @throws  unittest.AssertionFailedError
   */
  protected function assertRead($data) {
    with ($length= strlen($data), $r= $this->httpResponse(HttpConstants::STATUS_OK, ['Content-Length' => $length], $data)); {
    
      // Self-testing
      $this->assertEquals(HttpConstants::STATUS_OK, $r->statusCode());
      $this->assertEquals($length, (int)current($r->header('Content-Length')));
      
      // Check data
      $this->assertEquals($data, $this->readAll($r->in()));
    }
  }

  #[@test]
  public function readEmpty() {
    $this->assertRead('');
  }

  #[@test]
  public function readNonEmpty() {
    $this->assertRead('Hello World');
  }

  #[@test]
  public function readBinaryData() {
    $this->assertRead(
      "GIF89a\001\000\035\000\302\004\000\356\356\356\366\362\366\366\366\366\377\372".
      "\377\377\377\377\377\377\377\377\377\377\377\377\377!\371\004\001\n\000\007\000".
      ",\000\000\000\000\001\000\035\000\000\003\013H\272\323-P\200\031\002lK%\000;"
    );
  }

  #[@test]
  public function available() {
    with ($s= $this->httpResponse(HttpConstants::STATUS_OK, ['Content-Length' => 10], 'HelloWorld')->in()); {
      $this->assertNotEquals(0, $s->available(), 'before read #1');
      $this->assertEquals('Hello', $s->read(5));

      $this->assertNotEquals(0, $s->available(), 'before read #2');
      $this->assertEquals('World', $s->read(5));

      $this->assertEquals(0, $s->available(), 'after read #3');
    }
  }

  #[@test]
  public function availableWithChunks() {
    $chunks= "5\r\nHello\r\n"."5\r\nWorld\r\n"."0\r\n";
    with ($s= $this->httpResponse(HttpConstants::STATUS_OK, ['Transfer-Encoding' => 'chunked'], $chunks)->in()); {
      $this->assertNotEquals(0, $s->available(), 'before read #1');
      $this->assertEquals('Hello', $s->read(5));

      $this->assertNotEquals(0, $s->available(), 'before read #2');
      $this->assertEquals('World', $s->read(5));

      $this->assertEquals(0, $s->available(), 'after read #3');
    }
  }
 
  #[@test]
  public function availableAfterReadingAll() {
    with ($s= $this->httpResponse(HttpConstants::STATUS_OK, ['Content-Length' => 10], 'HelloWorld')->in()); {
      $this->readAll($s);
      $this->assertEquals(0, $s->available(), 'after read all');
    }
  }
 
  #[@test]
  public function readAfterReadingAll() {
    with ($s= $this->httpResponse(HttpConstants::STATUS_OK, ['Content-Length' => 10], 'HelloWorld')->in()); {
      $this->readAll($s);
      $this->assertEquals(null, $s->read(), 'after read all');
    }
  }

  #[@test]
  public function availableWhenBuffered() {
    with ($s= $this->httpResponse(HttpConstants::STATUS_OK, ['Content-Length' => 10], 'HelloWorld')->in()); {
      $s->read(5);
      $this->assertEquals(5, $s->available());
    }
  }
}
