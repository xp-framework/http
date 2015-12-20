<?php namespace peer\http;

/**
 * HTTP headers are key/value pairs transported in both request and response
 *
 * @test  xp://peer.http.unittest.HeaderTest
 */
class Header implements \lang\Value {
  private $name, $value;

  /**
   * Creates a new header
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

  /** @return string */
  public function toString() { return nameof($this).'('.$this->name.': '.$this->value.')'; }

  /** @return string */
  public function hashCode() { return md5($this->name.':'.$this->value); }

  /**
   * Compares this header to another value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    if ($value instanceof self) {
      if (0 !== $r= strcasecmp($this->name, $value->name)) return $r > 0 ? 1 : -1;
      if (0 !== $r= strcmp($this->value, $value->value)) return $r > 0 ? 1 : -1;
      return 0;
    }
    return 1;
  }
}