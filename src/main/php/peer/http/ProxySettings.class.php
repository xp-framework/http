<?php namespace peer\http;

abstract class ProxySettings extends \lang\Object {
  protected $proxies;
  protected $excludes;

  /**
   * Creates a new instance
   */
  public function __construct() {
    $this->infer();
  }

  /** @return bool Whether settings were found */
  public abstract function infer();

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