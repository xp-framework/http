<?php namespace peer\http;

use peer\Header;
use security\SecureString;

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
 * @see  http://www.owasp.org/downloads/http_authentication.txt
 * @see  rfc://2617 
 */
class BasicAuthorization extends Header {
  public
    $user = '',
    $pass = '';
  
  /**
   * Constructor
   *
   * @param   string $user
   * @param   var $pass security.SecureString or plain string
   */
  public function __construct($user, $pass) {
    $this->user= $user;

    if (!$pass instanceof SecureString) {
      $pass= new SecureString($pass);
    }

    $this->pass= $pass;
    parent::__construct('Authorization', 'Basic');
  }

  /**
   * Returns the username
   *
   * @return  string
   */    
  public function getUser() {
    return $this->user;
  }
  
  /**
   * Returns the password
   *
   * @return  string
   */    
  public function getPassword() {
    return $this->pass;
  }
  
  /**
   * Returns a BasicAuthorization object from header value; returns
   * FALSE on error.
   *
   * @param   stirng $value The header value
   * @return  peer.http.BasicAuthorization
   */    
  public static function fromValue($value) {
    if (!preg_match('/^Basic (.*)$/', $value, $matches)) return false;
    list($user, $password)= explode(':', base64_decode($matches[1]), 2);
    return new self($user, new SecureString($password));
  }
  
  /**
   * Get header value representation
   *
   * @return  string value
   */
  public function getValueRepresentation() {
    return $this->value.' '.base64_encode($this->user.':'.$this->pass->getCharacters());
  }
}
