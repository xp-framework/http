<?php namespace peer\http;

/**
 * Infers proxy settings from the Windows registry
 *
 * @test   xp://peer.http.unittest.RegistrySettingsTest
 */
class RegistrySettings extends ProxySettings {
  const SETTINGS= 'HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Internet Settings';

  /**
   * Creates a new instance
   *
   * @param  php.Com $shell
   */
  public function __construct($shell= null) {
    $this->shell= $shell ?: new \Com('WScript.Shell');
    parent::__construct();
  }

  /** @return bool */
  public function infer() {
    if ($this->shell->regRead(self::SETTINGS.'\ProxyEnable')) {
      $proxy= $this->shell->regRead(self::SETTINGS.'\ProxyServer');

      try {
        $this->excludes= explode(';', $this->shell->regRead(self::SETTINGS.'\ProxyOverride'));
      } catch (\Exception $ignored) {
        $this->excludes= [];
      }

      if (strstr($proxy, '=')) {
        foreach (explode(';', $proxy) as $setting) {
          sscanf($setting, '%[^=]=%[^:]:%d', $proto, $host, $port);
          $this->proxies[$proto]= new HttpProxy($host, $port, $this->excludes);
        }
      } else {
        $this->proxies['*']= new HttpProxy($proxy, null, $this->excludes);
      }
      return true;
    }
    return false;
  }
}