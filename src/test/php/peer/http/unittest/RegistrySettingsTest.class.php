<?php namespace peer\http\unittest;

use lang\ClassLoader;
use peer\http\HttpProxy;
use peer\http\proxy\RegistrySettings;
use unittest\{BeforeClass, Test, Values};

/**
 * Verifies inferring proxy settings from the environment
 *
 * @see    xp://peer.http.proxy.RegistrySettings
 */
class RegistrySettingsTest extends \unittest\TestCase {
  protected static $shell;

  /**
   * Defines mock for `WScript.Shell` COM object
   */
  #[BeforeClass]
  public static function defineMock() {
    $parent= class_exists(\lang\Object::class) ? 'lang.Object' : null;
    self::$shell= ClassLoader::defineClass('WScript_ShellMock', $parent, [], '{
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

  #[Test]
  public function excludes_separated_by_semicolons() {
    $settings= new RegistrySettings(self::$shell->newInstance([
      'ProxyEnable'    => 1,
      'ProxyServer'    => 'proxy.example.com:3128',
      'ProxyOverride'  => '192.168.12.14;example.com:80'
    ]));
    $this->assertEquals(['192.168.12.14', 'example.com:80'], $settings->excludes());
  }

  #[Test, Values(['http', 'https'])]
  public function no_proxy($scheme) {
    $settings= new RegistrySettings(self::$shell->newInstance([
      'ProxyEnable'    => 0
    ]));
    $this->assertEquals(HttpProxy::NONE, $settings->proxy($scheme));
  }

  #[Test, Values(['http', 'https'])]
  public function general_proxy($scheme) {
    $settings= new RegistrySettings(self::$shell->newInstance([
      'ProxyEnable'    => 1,
      'ProxyServer'    => 'proxy.example.com:3128'
    ]));
    $proxy= $settings->proxy($scheme);
    $this->assertEquals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }

  #[Test]
  public function http_proxy() {
    $settings= new RegistrySettings(self::$shell->newInstance([
      'ProxyEnable'    => 1,
      'ProxyServer'    => 'http=proxy.example.com:3128'
    ]));
    $proxy= $settings->proxy('http');
    $this->assertEquals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }

  #[Test]
  public function https_proxy() {
    $settings= new RegistrySettings(self::$shell->newInstance([
      'ProxyEnable'    => 1,
      'ProxyServer'    => 'https=proxy.example.com:3128'
    ]));
    $proxy= $settings->proxy('https');
    $this->assertEquals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }
}