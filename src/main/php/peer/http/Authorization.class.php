<?php namespace peer\http;

use lang\Object;
use security\SecureString;

abstract class Authorization extends Object {
  protected $username;
  protected $password;

  /** @return string */
  public function username() { return $this->username; }

  /** @param string u */
  public function setUsername($u) { $this->username= $u; }
  
  /** @return security.SecureString */
  public function password() { return $this->password; }

  /** @param security.SecureString p */
  public function setPassword(SecureString $p) { $this->password= $p; }

  /**
   * Sign HTTP request
   * 
   * @param  peer.http.HttpRequest $request
   */
  abstract function sign(HttpRequest $request);

  public static function fromChallenge($header, $user, $pass) {
    throw new MethodNotImplementedException(__METHOD__, 'Should be abstract');
  }
}