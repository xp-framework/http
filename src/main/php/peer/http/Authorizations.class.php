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
        if (!preg_match_all('#(([a-z]+)=([^,$]+))#m', $header, $matches, PREG_SET_ORDER)) {
          throw new IllegalStateException('Invalid WWW-Authenticate line');
        }

        $values= [];
        foreach ($matches as $m) {
          $values[$m[2]]= $m[3];
        }

        $auth= new DigestAuthorization(
          $values['realm'],
          $values['qop'],
          $values['nonce'],
          $values['opaque']
        );
        $auth->username($user);
        $auth->password($pass);

        break;
      }

      case 'Basic ' === substr($header, 0, strlen('Basic ')): {
        return new BasicAuthorization($this->user, $this->pass);
        break;
      }

      default: {
        throw new IllegalStateException('Unknown authorization type.');
      }
    }
  }
}