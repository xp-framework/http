<?php namespace peer\http\unittest;

use peer\http\Header;
use test\Assert;
use test\{Test, TestCase, Values};

class HeaderTest {

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
    Assert::equals('Name', (new Header('Name', 'Value'))->name());
  }

  #[Test]
  public function value_accessor() {
    Assert::equals('Value', (new Header('Name', 'Value'))->value());
  }

  #[Test]
  public function string_representation() {
    Assert::equals('peer.http.Header(Name: Value)', (new Header('Name', 'Value'))->toString());
  }

  #[Test, Values(from: 'comparison')]
  public function compare($a, $b, $expect) {
    Assert::equals($expect, $a->compareTo($b));
  }
}