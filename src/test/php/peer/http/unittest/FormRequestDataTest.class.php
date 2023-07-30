<?php namespace peer\http\unittest;

use peer\http\{FormData, FormRequestData};
use unittest\{Assert, Before, Test};

class FormRequestDataTest {

  #[Test]
  public function addPart() {
    $fixture= new FormRequestData();
    $data= new FormData('key', 'value');
    Assert::equals($data, $fixture->addPart($data));
  }

  #[Test]
  public function withPart() {
    $fixture= new FormRequestData();
    $data= new FormData('key', 'value');
    Assert::equals($fixture, $fixture->withPart($data));
  }

  #[Test]
  public function simpleMimeRepresentation() {
    $fixture= new FormRequestData();
    $fixture->addPart(new FormData('key', 'value'));

    Assert::equals(
      "--".$fixture->getBoundary()."\r\n".
      "Content-Disposition: form-data; name=\"key\"\r\n\r\n".
      "value\r\n--".$fixture->getBoundary()."--\r\n",

      $fixture->getData()
    );
  }

  #[Test]
  public function noDefaultTypeMimeRepresentation() {
    $fixture= new FormRequestData();
    $fixture->addPart(new FormData('key', 'value', 'text/html'));

    Assert::equals(
      "--".$fixture->getBoundary()."\r\n".
      "Content-Disposition: form-data; name=\"key\"\r\n".
      "Content-Type: text/html\r\n\r\n".
      "value\r\n--".$fixture->getBoundary()."--\r\n",

      $fixture->getData()
    );
  }

  #[Test]
  public function noDefaultCharsetMimeRepresentation() {
    $fixture= new FormRequestData();
    $fixture->addPart(new FormData('key', 'value', 'text/plain', 'utf-16'));

    Assert::equals(
      "--".$fixture->getBoundary()."\r\n".
      "Content-Disposition: form-data; name=\"key\"\r\n".
      "Content-Type: text/plain; charset=\"utf-16\"\r\n\r\n".
      "value\r\n--".$fixture->getBoundary()."--\r\n",

      $fixture->getData()
    );
  }
}