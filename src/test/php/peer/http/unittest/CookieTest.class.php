<?php namespace peer\http\unittest;

use peer\http\Cookie;

/**
 * Test cookie class
 *
 * @see    xp://peer.http.Cookie
 */
class CookieTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Cookie('name', 'value');
  }

  #[@test]
  public function name_accessor() {
    $this->assertEquals('name', (new Cookie('name', 'value'))->name());
  }

  #[@test]
  public function value_accessor() {
    $this->assertEquals('value', (new Cookie('name', 'value'))->value());
  }

  #[@test]
  public function string_representation() {
    $this->assertEquals('peer.http.Cookie(name=value)', (new Cookie('name', 'value'))->toString());
  }

  #[@test]
  public function equality() {
    $this->assertEquals(new Cookie('name', 'value'), new Cookie('name', 'value'));
  }

  #[@test]
  public function cookies_with_different_values_are_not_equal() {
    $this->assertNotEquals(new Cookie('name', 'value'), new Cookie('name', 'different'));
  }

  #[@test]
  public function cookies_with_different_names_are_not_equal() {
    $this->assertNotEquals(new Cookie('name', 'value'), new Cookie('different', 'value'));
  }
}
