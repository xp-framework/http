<?php namespace peer\http\proxy;

use peer\http\HttpProxy;

abstract class ProxySettings {
  protected $proxies;
  protected $excludes;
  protected $detected;

  /**
   * Creates a new instance
   */
  public function __construct() {
    $this->detected= $this->infer();
  }

  /** @return bool Whether settings were found */
  protected abstract function infer();

  /** @return bool */
  public function detected() { return $this->detected; }

  /** @return string[] */
  public function excludes() { return $this->excludes; }

  /**
   * Returns a proxy for a given scheme, or `HttpProxy::NONE` if there is none.
   *
   * @param  string $scheme
   * @return peer.http.HttpProxy
   */
  public function proxy($scheme) {
    if (isset($this->proxies[$scheme])) {
      return $this->proxies[$scheme];
    } else if (isset($this->proxies['*'])) {
      return $this->proxies['*'];
    } else {
      return HttpProxy::NONE;
    }
  }
}