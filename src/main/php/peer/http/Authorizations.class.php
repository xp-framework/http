<?php namespace peer\http;

use security\SecureString;
use lang\IllegalStateException;

/**
 * Authorization factory class for HTTP
 *
 */
class Authorizations extends \lang\Object {

  /**
   * Create authorization from challenge data from given
   * HTTP request.
   *
   * @param  peer.http.HttpResponse $response
   * @param  string $user
   * @param  security.SecureString $pass
   * @return var peer.http.BasicAuthorization or peer.http.DigestAuthentication
   * @throws lang.IllegalStateException If request hadn't challenged
   * @throws lang.IllegalStateException If HTTP status not equal 401
   * @throws lang.IllegalStateException If Unknown authorization type was used
   */
  public static function fromResponse(HttpResponse $response, $user, SecureString $pass) {
    if (HttpConstants::STATUS_AUTHORIZATION_REQUIRED !== $response->getStatusCode()) {
      throw new IllegalStateException('Request had not been rejected, will not create authorization.');
    }

    if (1 != sizeof($response->header('WWW-Authenticate'))) {
      throw new IllegalStateException('No authentication type indicated.');
    }

    $header= $response->header('WWW-Authenticate')[0];
    switch (true) {
      case 'Digest ' === substr($header, 0, strlen('Digest ')): {
        return DigestAuthorization::fromChallenge($header, $user, $pass);
      }

      case 'Basic ' === substr($header, 0, strlen('Basic ')): {
        return new BasicAuthorization($this->user, $this->pass);
      }

      default: {
        throw new IllegalStateException('Unknown authorization type.');
      }
    }
  }
}