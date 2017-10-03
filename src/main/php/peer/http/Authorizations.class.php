<?php namespace peer\http;

use lang\XPClass;
use lang\reflect\TargetInvocationException;
use util\Secret;
use lang\IllegalStateException;

/**
 * Authorization factory class for HTTP
 *
 */
final class Authorizations {
  const AUTH_HEADER= 'WWW-Authenticate';
  protected $impl= [
    ['startsWith' => 'Basic ', 'impl' => 'peer.http.BasicAuthorization'],
    ['startsWith' => 'Digest ', 'impl' => 'peer.http.DigestAuthorization']
  ];

  public static $CONCEAL, $REVEAL;

  static function __static() {
    if (class_exists('util\\Secret')) {
      self::$CONCEAL= function($plain) { return new Secret($plain); };
      self::$REVEAL= function($secret) { return $secret->reveal(); };
    } else {

      // BC with older XP framework versions
      self::$CONCEAL= function($plain) { return new \security\SecureString($plain); };
      self::$REVEAL= function($secret) { return $secret->getCharacters(); };
    }
  }

  public function required(HttpResponse $response) {
    return HttpConstants::STATUS_AUTHORIZATION_REQUIRED == $response->getStatusCode();
  }

  /**
   * Create authorization instance from a response
   *
   * @param  peer.http.HttpResponse $response
   * @param  string $user
   * @param  util.Secret|security.SecureString $pass
   * @return peer.http.Authorization
   * @throws lang.IllegalStateException If request hadn't challenged
   * @throws lang.IllegalStateException If HTTP status not equal 401
   * @throws lang.IllegalStateException If Unknown authorization type was used
   */
  public function create(HttpResponse $response, $user, $pass) {
    if (!$this->required($response)) {
      throw new IllegalStateException('Request had not been rejected, will not create authorization.');
    }

    if (1 != sizeof($response->header(self::AUTH_HEADER))) {
      throw new IllegalStateException('No authentication type indicated.');
    }

    $header= $response->header(self::AUTH_HEADER)[0];
    foreach ($this->impl as $impl) {
      if (0 == strncmp($impl['startsWith'], $header, strlen($impl['startsWith']))) {
        try {
          return XPClass::forName($impl['impl'])->getMethod('fromChallenge')->invoke(null, [$header, $user, $pass]);
        } catch (TargetInvocationException $e) {
          throw $e->getCause();
        }
      }
    }

    throw new IllegalStateException('Unknown authorization type.');
  }

  /**
   * Create authorization from challenge data from given
   * HTTP request.
   *
   * @param  peer.http.HttpResponse $response
   * @param  string $user
   * @param  string|util.Secret|security.SecureString $pass
   * @return peer.http.Authorization
   * @throws lang.IllegalStateException If request hadn't challenged
   * @throws lang.IllegalStateException If HTTP status not equal 401
   * @throws lang.IllegalStateException If Unknown authorization type was used
   */
  public static function fromResponse(HttpResponse $response, $user, $pass) {
    $self= new self();
    if ($pass instanceof Secret || $pass instanceof \security\SecureString) {
      return $self->create($response, $user, $pass);
    } else {
      return $self->create($response, $user, self::$CONCEAL->__invoke($pass));
    }
  }
}