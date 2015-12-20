<?php namespace peer\http;

use lang\Object;
use util\Secret;

abstract class Authorization extends Object {
  protected $username;
  protected $password;

  /** @return string */
  public function username() { return $this->username; }

  /** @param string u */
  public function setUsername($u) { $this->username= $u; }
  
  /** @return util.Secret */
  public function password() { return $this->password; }

  /** @param util.Secret p */
  public function setPassword(Secret $p) { $this->password= $p; }

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