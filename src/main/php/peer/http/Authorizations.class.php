<?php namespace peer\http;

use security\SecureString;
use lang\IllegalStateException;

class Authorizations extends \lang\Object {

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