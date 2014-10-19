<?php namespace peer\http\proxy;

use peer\http\HttpProxy;

/**
 * Infers proxy settings from the environment
 *
 * @see    https://wiki.archlinux.org/index.php/Proxy_settings
 * @see    https://github.com/composer/composer/issues/1318#issuecomment-10328203
 * @see    https://bugs.ruby-lang.org/issues/6546#note-9
 * @test   xp://peer.http.unittest.EnvironmentSettingsTest
 */
class EnvironmentSettings extends ProxySettings {
  protected static $variables= [
    'http_proxy'  => 'http',
    'https_proxy' => 'https',
    'HTTP_PROXY'  => 'http',
    'HTTPS_PROXY' => 'https',
    'all_proxy'   => '*'
  ];

  static function __static() {
    if (getenv('SERVER_PROTOCOL')) {
      unset(self::$variables['HTTP_PROXY']);
    }
  }

  /** @return bool */
  protected function infer() {
    if ($no= getenv('no_proxy')) {
      $this->excludes= explode(',', $no);
    } else {
      $this->excludes= [];
    }

    $inferred= false;
    $this->proxies= [];
    foreach (self::$variables as $var => $proto) {
      if ($env= getenv($var)) {
        $inferred= true;
        if (false === ($p= strpos($env, '://'))) {
          $this->proxies[$proto]= new HttpProxy($env, null, $this->excludes);
        } else {
          $this->proxies[$proto]= new HttpProxy(rtrim(substr($env, $p + 3), '/'), null, $this->excludes);
        }
      }
    }
    return $inferred;
  }
}