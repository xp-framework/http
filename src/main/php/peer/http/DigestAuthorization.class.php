<?php namespace peer\http;

use peer\Header;
use security\SecureString;

class DigestAuthorization extends Header {

  /** server values */
  private $realm;
  private $qop;
  private $nonce;
  private $opaque;

  /** client credentials */
  private $username;
  private $password;

  /** Internal state */
  private $counter= 1;
  private $cnonce;

  public function __construct($realm, $qop, $nonce, $opaque) {
    parent::__construct('Authorization', 'Digest');

    $this->realm= $realm;
    $this->qop= $qop;
    $this->nonce= $nonce;
    $this->opaque= $opaque;

    $this->cnonce();
  }

  public function username($u) {
    $this->username= $u;
  }

  public function password(SecureString $p) {
    $this->password= $p;
  }

  public function responseFor(HttpRequest $request) {
    return md5(implode(':', [
      $this->ha1(),
      $this->nonce,
      sprintf('%08x', $this->counter),
      $this->cnonce,
      $this->qop(),
      $this->ha2($request)
    ]));
  }

  private function ha1() {
    return md5(implode(':', [$this->username, $this->realm, $this->password->getCharacters()]));
  }

  private function ha2($request) {
    return md5(implode(':', [strtoupper($request->method), $request->getUrl()->getPath()]));
  }

  private function qop() {
    return 'auth';
  }

  public function cnonce($c= null) {
    if (null === $c) {
      $c= substr(md5(uniqid(time())), 0, 8);
    }

    $this->cnonce= $c;
  }

  public function equals($o) {
    if (!$o instanceof self) return false;

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
      $s.= sprintf("  [ %8s ] %s\n", $attr, \xp::stringOf($this->{$attr}));
    }
    return $s.="}\n";
  }


}