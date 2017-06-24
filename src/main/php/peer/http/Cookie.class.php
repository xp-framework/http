<?php namespace peer\http;

/**
 * A cookie as per RFC 2109
 *
 * @test xp://peer.http.unittest.CookieTest
 */
class Cookie extends \lang\Object {
  private $name, $value;

  /**
   * Creates a new cookie
   *
   * @param  string $name
   * @param  string $value
   */
  public function __construct($name, $value) {
    $this->name= $name;
    $this->value= $value;
  }

  /** @return string */
  public function name() { return $this->name; }

  /** @return string */
  public function value() { return $this->value; }

  /**
   * Creates a string representation
   *
   * @return string
   */
  public function toString() {
    return $this->getClassName().'('.$this->name.'='.$this->value.')';
  }

  /**
   * Checks whether a given value is equal to this cookie
   *
   * @param  var $cmp
   * @return bool
   */
  public function equals($cmp) {
    return $cmp instanceof self && (
      $this->name === $cmp->name &&
      $this->value === $cmp->value
    );
  }
}