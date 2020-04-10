<?php namespace peer\http\unittest;

use peer\http\{FormData, FormRequestData};
use unittest\TestCase;

/**
 * TestCase for FormRequestData and FormData classes
 *
 * @see  xp://peer.http.FormRequestData
 * @see  xp://peer.http.FormData
 */
class FormRequestDataTest extends TestCase {
  protected $fixture  = null;

  /**
   * Setup test case
   */
  public function setUp() {
    $this->fixture= new FormRequestData();
  }

  #[@test]
  public function addPart() {
    $data= new FormData('key', 'value');
    $this->assertEquals($data, $this->fixture->addPart($data));
  }

  #[@test]
  public function withPart() {
    $data= new FormData('key', 'value');
    $this->assertEquals($this->fixture, $this->fixture->withPart($data));
  }

  #[@test]
  public function simpleMimeRepresentation() {
    $this->fixture->addPart(new FormData('key', 'value'));

    $this->assertEquals(
      "--".$this->fixture->getBoundary()."\r\n".
      "Content-Disposition: form-data; name=\"key\"\r\n\r\n".
      "value\r\n--".$this->fixture->getBoundary()."--\r\n",

      $this->fixture->getData()
    );
  }

  #[@test]
  public function noDefaultTypeMimeRepresentation() {
    $this->fixture->addPart(new FormData('key', 'value', 'text/html'));

    $this->assertEquals(
      "--".$this->fixture->getBoundary()."\r\n".
      "Content-Disposition: form-data; name=\"key\"\r\n".
      "Content-Type: text/html\r\n\r\n".
      "value\r\n--".$this->fixture->getBoundary()."--\r\n",

      $this->fixture->getData()
    );
  }

  #[@test]
  public function noDefaultCharsetMimeRepresentation() {
    $this->fixture->addPart(new FormData('key', 'value', 'text/plain', 'utf-16'));

    $this->assertEquals(
      "--".$this->fixture->getBoundary()."\r\n".
      "Content-Disposition: form-data; name=\"key\"\r\n".
      "Content-Type: text/plain; charset=\"utf-16\"\r\n\r\n".
      "value\r\n--".$this->fixture->getBoundary()."--\r\n",

      $this->fixture->getData()
    );
  }
}