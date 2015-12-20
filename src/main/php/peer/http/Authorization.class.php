<?php namespace peer\http;

use util\Secret;

abstract class Authorization extends \lang\Object {
  protected $username, $password;

  /** @return string */
  public function username() { return $this->username; }

  /** @param string $value */
  public function setUsername($value) { $this->username= $value; }
  
  /** @return util.Secret */
  public function password() { return $this->password; }

  /** @param util.Secret|string $value */
  public function setPassword($value) { $this->password= $value instanceof Secret ? $value : new Secret($value); }

  /**
   * Sign HTTP request
   * 
   * @param  peer.http.HttpRequest $request
   */
  abstract function sign(HttpRequest $request);

  /**
   * Creates from challenge. Implemented in subclasses
   *
   * @param  string $header
   * @param  string $user
   * @param  util.Secret $pass
   * @return peer.http.DigestAuthorization
   */
  public static function fromChallenge($header, $user, $pass) {
    throw new MethodNotImplementedException(__METHOD__, 'Should be abstract');
  }
}