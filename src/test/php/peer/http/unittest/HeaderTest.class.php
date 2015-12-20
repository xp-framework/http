<?php namespace peer\http\unittest;

use peer\http\Header;

class HeaderTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Header('Name', 'Value');
  }

  #[@test]
  public function name_accessor() {
    $this->assertEquals('Name', (new Header('Name', 'Value'))->name());
  }

  #[@test]
  public function value_accessor() {
    $this->assertEquals('Value', (new Header('Name', 'Value'))->value());
  }

  #[@test]
  public function string_representation() {
    $this->assertEquals('peer.http.Header(Name: Value)', (new Header('Name', 'Value'))->toString());
  }

  #[@test, @values([
  #  [new Header('Host', 'example.com'), new Header('Connection', 'keep-alive'), 1],
  #  [new Header('Connection', 'close'), new Header('Connection', 'keep-alive'), -1],
  #  [new Header('Accept', '*/*'), new Header('Accept', '*/*'), 0],
  #  [new Header('ACCEPT', '*/*'), new Header('Accept', '*/*'), 0]
  #])]
  public function compareTo($a, $b, $expect) {
    $this->assertEquals($expect, $a->compareTo($b));
  }
}