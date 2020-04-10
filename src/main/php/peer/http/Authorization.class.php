<?php namespace peer\http;

abstract class Authorization {
  protected $username, $password;

  /** @return string */
  public function username() { return $this->username; }

  /** @param string $value */
  public function setUsername($value) { $this->username= $value; }
  
  /** @return util.Secret|security.SecureString */
  public function password() { return $this->password; }

  /** @param util.Secret|security.SecureString $value */
  public function setPassword($value) { $this->password= $value; }

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
   * @param  util.Secret|security.SecureString $pass
   * @return peer.http.DigestAuthorization
   */
  public static function fromChallenge($header, $user, $pass) {
    throw new MethodNotImplementedException(__METHOD__, 'Should be abstract');
  }
}