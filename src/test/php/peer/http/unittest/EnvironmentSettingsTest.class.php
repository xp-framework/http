<?php namespace peer\http\unittest;

use peer\http\HttpProxy;
use peer\http\proxy\EnvironmentSettings;

/**
 * Verifies inferring proxy settings from the environment
 *
 * @see    xp://peer.http.proxy.EnvironmentSettings
 */
class EnvironmentSettingsTest extends \unittest\TestCase {

  #[@test, @values(['http', 'https'])]
  public function no_proxy($scheme) {
    $this->assertEquals(HttpProxy::NONE, (new EnvironmentSettings())->proxy($scheme));
  }

  #[@test]
  public function excludes_from_no_proxy() {
    putenv('no_proxy=*');
    $this->assertEquals(['*'], (new EnvironmentSettings())->excludes());
  }

  #[@test]
  public function excludes_empty() {
    putenv('no_proxy');
    $this->assertEquals([], (new EnvironmentSettings())->excludes());
  }

  #[@test, @values(['http_proxy=proxy.example.com:3128', 'HTTP_PROXY=proxy.example.com:3128'])]
  public function http_proxy($env) {
    putenv($env);
    $proxy= (new EnvironmentSettings())->proxy('http');
    $this->assertEquals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }

  #[@test, @values(['https_proxy=proxy.example.com:3128', 'HTTPS_PROXY=proxy.example.com:3128'])]
  public function https_proxy($env) {
    putenv($env);
    $proxy= (new EnvironmentSettings())->proxy('https');
    $this->assertEquals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }

  #[@test, @values(['http', 'https'])]
  public function all_proxy($scheme) {
    putenv('all_proxy=proxy.example.com:3128');
    $proxy= (new EnvironmentSettings())->proxy($scheme);
    $this->assertEquals(['proxy.example.com', 3128], [$proxy->host(), $proxy->port()]);
  }
}
