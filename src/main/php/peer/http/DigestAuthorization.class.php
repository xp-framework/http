<?php namespace peer\http;

use peer\Header;
use security\SecureString;

class DigestAuthorization extends Header {
  private $realm;
  private $qop;
  private $nonce;
  private $opaque;
  private $username;
  private $password;

  public function __construct($realm, $qop, $nonce, $opaque) {
    parent::__construct('Authorization', 'Digest');

    $this->realm= $realm;
    $this->qop= $qop;
    $this->nonce= $nonce;
    $this->opaque= $opaque;
  }

  public function username($u) {
    $this->username= $u;
  }

  public function password(SecureString $p) {
    $this->password= $p;
  }

  public function equals($o) {
    if ($o instanceof self) return false;

    return (
      $o->realm === $this->realm &&
      $o->qop === $this->qop &&
      $o->nonce === $this->nonce &&
      $o->opaque === $this->opaque
    );
  }

  public function toString() {
    $s= $this->getClassName().' ('.$this->hashCode().") {\n";
    foreach (['realm', 'qop', 'nonce', 'opaque', 'username'] as $attr) {
      $s.= sprintf("  [ %8s ] %s\n", $attr, $this->{$attr});
    }
    return $s.="}\n";
  }


}