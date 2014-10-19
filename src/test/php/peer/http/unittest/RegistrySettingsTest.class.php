<?php namespace peer\http\unittest;

use peer\http\RegistrySettings;
use peer\http\HttpProxy;
use lang\ClassLoader;

/**
 * Verifies inferring proxy settings from the environment
 *
 * @see    xp://peer.http.RegistrySettings
 */
class RegistrySettingsTest extends \unittest\TestCase {
  protected static $shell;

  /**
   * Defines mock for `WScript.Shell` COM object
   */
  #[@beforeClass]
  public static function defineMock() {
    self::$shell= ClassLoader::defineClass('WScript_ShellMock', 'lang.Object', [], '{
      protected $keys;

      public function __construct($keys) {
        $this->keys= $keys;
      }

      public function regRead($key) {
        $k= substr($key, strrpos($key, "\\\\") + 1);
        if (isset($this->keys[$k])) return $this->keys[$k];

        throw new \Exception("Cannot read $key from registry");
      }
    }');
  }

  #[@test]
  public function excludes_separated_by_semicolons() {
    $settings= new RegistrySettings(self::$shell->newInstance([
      'ProxyEnable'    => 1,
      'ProxyServer'    => 'proxy.example.com:3128',
      'ProxyOverride'  => '192.168.12.14;example.com:80'
    ]));
    $this->assertEquals(['192.168.12.14', 'example.com:80'], $settings->excludes());
  }

  #[@test, @values(['http', 'https'])]
  public function no_proxy($scheme) {
    $settings= new RegistrySettings(self::$shell->newInstance([
      'ProxyEnable'    => 0
    ]));
    $this->assertEquals(HttpProxy::NONE, $settings->proxy($scheme));
  }

  #[@test, @values(['http', 'https'])]
  public function general_proxy($scheme) {
    $settings= new RegistrySettings(self::$shell->newInstance([
      'ProxyEnable'    => 1,
      'ProxyServer'    => 'proxy.example.com:3128'
    ]));
    $proxy= $settings->proxy($scheme);
    $this->assertEquals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }

  #[@test]
  public function http_proxy() {
    $settings= new RegistrySettings(self::$shell->newInstance([
      'ProxyEnable'    => 1,
      'ProxyServer'    => 'http=proxy.example.com:3128'
    ]));
    $proxy= $settings->proxy('http');
    $this->assertEquals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }

  #[@test]
  public function https_proxy() {
    $settings= new RegistrySettings(self::$shell->newInstance([
      'ProxyEnable'    => 1,
      'ProxyServer'    => 'https=proxy.example.com:3128'
    ]));
    $proxy= $settings->proxy('https');
    $this->assertEquals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }
}
