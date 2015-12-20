HTTP protocol support for the XP Framework ChangeLog
========================================================================

## ?.?.? / ????-??-??

* Refactored API to provide its own dedicated `Header` class inside the
  `peer.http` package instead of relying on the deprecated one in XP
  framework core.
  (@thekid)
* Refactored API to use `util.Secret` class instead of the deprecated
  `security.SecureString` internally.
  (@thekid)

## 6.1.3 / 2015-12-08

* Merged PR #14: Port PR xp-framework/xp-framework#381 - `getPayload()`
  failed when array key contained spaces.
  @melogamepay, @kiesel
* Fixed issue #12: Fatal error when ext/com is not present on Windows
  @thekid

## 6.1.2 / 2015-09-26

* Merged PR #11: Use short array syntax / ::class in annotations - @thekid

## 6.1.1 / 2015-07-12

* Added forward compatibility with XP 6.4.0 - @thekid
* Added preliminary PHP 7 support (alpha2, beta1) - @thekid

## 6.1.0 / 2015-04-28

* Changed functionality to always send data in body if a RequestData instance
  is given. See pull requests #4 and #10.
  (@kiesel, @thekid)

## 6.0.1 / 2015-02-12

* Changed dependency to use XP ~6.0 (instead of dev-master) - @thekid

## 6.0.0 / 2015-10-01

* Added new method `HttpResponse::in()` as replacement for `getInputStream()`
  being consistent with core framework (peer.Socket and io.File classes)
  (@thekid)
* Work to support HTTP proxies transparently:
  - Detect and use system proxy settings per default (PR #3)
  - Forcing a direct connection possible using `setProxy(HttpProxy::NONE)`
  - Implement using "CONNECT" for tunneling HTTPS through proxy (PR #2)
  - Use "GET http://..." for HTTP through a proxy
  (@thekid)
* Heads up: Converted classes to PHP 5.3 namespaces - (@thekid)
