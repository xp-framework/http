<?php namespace peer\http;

use lang\Object;
use util\Secret;

/**
 * Basic Authorization header
 *
 * <quote>
 * "HTTP/1.0", includes the specification for a Basic Access
 * Authentication scheme. This scheme is not considered to be a secure
 * method of user authentication (unless used in conjunction with some
 * external secure system such as SSL), as the user name and
 * password are passed over the network as cleartext.
 * </quote>
 *
 * @see  rfc://2617 
 */
class BasicAuthorization extends Authorization {
  
  /**
   * Constructor
   *
   * @param   string $user
   * @param   var $pass util.Secret or plain string
   */
  public function __construct($user, $pass) {
    $this->setUsername($user);

    if ($pass instanceof Secret) {
      $this->setPassword($pass);
    } else {
      $this->setPassword(new Secret($pass));
    }
  }

  /** @return string */
  public function getUser() { return $this->username; }
  
  /**
   * Returns a BasicAuthorization object from header value; returns
   * FALSE on error.
   *
   * @param   string $value The header value
   * @return  peer.http.BasicAuthorization
   */    
  public static function fromValue($value) {
    if (!preg_match('/^Basic (.*)$/', $value, $matches)) return false;
    list($user, $password)= explode(':', base64_decode($matches[1]), 2);
    return new self($user, new Secret($password));
  }

  /**
   * Create BasicAuthorization object from challenge
   *
   * @param  string $header
   * @param  string $user
   * @param  security.Secret $pass
   * @return self
   */
  public static function fromChallenge($header, $user, $pass) {
    return new self($user, $pass);
  }
  
  /**
   * Get header value representation
   *
   * @return  string value
   */
  public function getValueRepresentation() {
    return 'Basic '.base64_encode($this->username.':'.$this->password->reveal());
  }

  /**
   * Sign HTTP request
   *
   * @param  peer.http.HttpRequest $request
   */
  public function sign(HttpRequest $request) {
    $request->setHeader('Authorization', $this->getValueRepresentation());
  }

  /**
   * Retrieve string representation
   *
   * @return string
   */
  public function toString() {
    return $this->getClassName().' { username = "'.$this->username.'" }';
  }
}
