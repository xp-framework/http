<?php namespace peer\http\unittest;

use peer\http\Header;
use unittest\{Test, Values, TestCase};

class HeaderTest extends TestCase {

  /** @return iterable */
  private function comparison() {
    yield [new Header('Host', 'example.com'), new Header('Connection', 'keep-alive'), 1];
    yield [new Header('Connection', 'close'), new Header('Connection', 'keep-alive'), -1];
    yield [new Header('Accept', '*/*'), new Header('Accept', '*/*'), 0];
    yield [new Header('ACCEPT', '*/*'), new Header('Accept', '*/*'), 0];
  }

  #[Test]
  public function can_create() {
    new Header('Name', 'Value');
  }

  #[Test]
  public function name_accessor() {
    $this->assertEquals('Name', (new Header('Name', 'Value'))->name());
  }

  #[Test]
  public function value_accessor() {
    $this->assertEquals('Value', (new Header('Name', 'Value'))->value());
  }

  #[Test]
  public function string_representation() {
    $this->assertEquals('peer.http.Header(Name: Value)', (new Header('Name', 'Value'))->toString());
  }

  #[Test, Values('comparison')]
  public function compare($a, $b, $expect) {
    $this->assertEquals($expect, $a->compareTo($b));
  }
}