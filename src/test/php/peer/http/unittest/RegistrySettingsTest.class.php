<?php namespace peer\http\unittest;

use Exception;
use lang\ClassLoader;
use peer\http\HttpProxy;
use peer\http\proxy\RegistrySettings;
use test\{Assert, Before, Test, Values};

class RegistrySettingsTest {
  protected $shell;

  #[Before]
  public function defineMock() {
    $this->shell= ClassLoader::defineClass('WScript_ShellMock', null, [], [
      'keys' => null,
      '__construct' => function($keys) { $this->keys= $keys; },
      'regRead' => function($key) {
        $k= substr($key, strrpos($key, '\\') + 1);
        if (isset($this->keys[$k])) return $this->keys[$k];

        throw new Exception("Cannot read {$key} from registry");        
      }
    ]);
  }

  #[Test]
  public function excludes_separated_by_semicolons() {
    $settings= new RegistrySettings($this->shell->newInstance([
      'ProxyEnable'    => 1,
      'ProxyServer'    => 'proxy.example.com:3128',
      'ProxyOverride'  => '192.168.12.14;example.com:80'
    ]));
    Assert::equals(['192.168.12.14', 'example.com:80'], $settings->excludes());
  }

  #[Test, Values(['http', 'https'])]
  public function no_proxy($scheme) {
    $settings= new RegistrySettings($this->shell->newInstance([
      'ProxyEnable'    => 0
    ]));
    Assert::equals(HttpProxy::NONE, $settings->proxy($scheme));
  }

  #[Test, Values(['http', 'https'])]
  public function general_proxy($scheme) {
    $settings= new RegistrySettings($this->shell->newInstance([
      'ProxyEnable'    => 1,
      'ProxyServer'    => 'proxy.example.com:3128'
    ]));
    $proxy= $settings->proxy($scheme);
    Assert::equals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }

  #[Test]
  public function http_proxy() {
    $settings= new RegistrySettings($this->shell->newInstance([
      'ProxyEnable'    => 1,
      'ProxyServer'    => 'http=proxy.example.com:3128'
    ]));
    $proxy= $settings->proxy('http');
    Assert::equals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }

  #[Test]
  public function https_proxy() {
    $settings= new RegistrySettings($this->shell->newInstance([
      'ProxyEnable'    => 1,
      'ProxyServer'    => 'https=proxy.example.com:3128'
    ]));
    $proxy= $settings->proxy('https');
    Assert::equals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }
}