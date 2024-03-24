HTTP protocol support for the XP Framework ChangeLog
========================================================================

## ?.?.? / ????-??-??

## 10.3.0 / 2024-03-24

* Made compatible with XP 12 - @thekid
* Added PHP 8.4 to the test matrix - @thekid

## 10.2.0 / 2023-07-30

* Added PHP 8.3 to test matrix, migrated test suite to new testing library
  `xp-framework/test`, see xp-framework/rfc#344
  (@thekid)
* Refactored `peer.http.Authorizations` to use dynamic invocations instead
  of reflection. See xp-framework/rfc#338
  (@thekid)

## 10.1.0 / 2023-06-16

* Merged PR #26: Implement flushing chunked output streams - @thekid

## 10.0.3 / 2022-02-27

* Fixed "Creation of dynamic property" warnings in PHP 8.2 - @thekid

## 10.0.2 / 2021-10-21

* Made compatible with XP 11 and `xp-framework/logging` version 11.0.0
  (@thekid)

## 10.0.1 / 2021-03-14

* Fixed *stristr(): Passing null to parameter 1 ($haystack) of type string
  is deprecated* in PHP 8.1
  (@thekid)

## 10.0.0 / 2020-04-10

* Implemented xp-framework/rfc#334: Drop PHP 5.6:
  . **Heads up:** Minimum required PHP version now is PHP 7.0.0
  . Rewrote code base, grouping use statements
  . Rewrote `isset(X) ? X : default` to `X ?? default`
  (@thekid)

## 9.1.4 / 2019-12-24

* Fixed SSL handling when the server does not support TLS, see PR #25
  (@patzerr, @thekid).

## 9.1.3 / 2019-12-24

* Fixed HTTP *HEAD* requests when using `ext/curl` - @patzerr, @thekid

## 9.1.2 / 2019-12-01

* Made compatible with XP 10 - @thekid

## 9.1.1 / 2018-10-04

* Added support for specifying TLS v1.0 and TLS v1.1 - @thekid
* Merged PR #24: Add support for TLS v1.2 - @treuter, @thekid

## 9.1.0 / 2018-08-30

* Merged PR #23: Streaming requests - @thekid

## 9.0.3 / 2018-08-24

* Made compatible with `xp-framework/logging` version 9.0.0 - @thekid

## 9.0.2 / 2018-04-02

* Fixed compatiblity with PHP 7.3 - @thekid

## 9.0.1 / 2018-04-02

* Fixed compatiblity with PHP 7.2 - @thekid

## 9.0.0 / 2017-06-20

* Merged PR #20: XP9 Compatibility - @thekid

## 8.0.2 / 2017-06-05

* Fixed `Fatal error (Class 'peer\Header' not found)` - @thekid

## 8.0.1 / 2016-08-29

* Made compatible with xp-framework/networking v8.0.0 - @thekid

## 8.0.0 / 2016-08-28

* **Heads up: Dropped PHP 5.5 support!** - @thekid
* Added forward compatibility with XP 8.0.0 - @thekid

## 7.0.1 / 2016-06-10

* Allowed IPV6 addresses in PROXY environment variables, e.g. `[::1]:3128`
  (@thekid)
* Fixed issue #15: NO_PROXY - @thekid

## 7.0.0 / 2016-02-21

* **Adopted semantic versioning. See xp-framework/rfc#300** - @thekid 
* Added version compatibility with XP 7 - @thekid

## 6.2.1 / 2016-01-23

* Fix code to use `nameof()` instead of the deprecated `getClassName()`
  method from lang.Generic. See xp-framework/core#120
  (@thekid)

## 6.2.0 / 2015-12-20

* Refactored API to provide its own dedicated `Header` class inside the
  `peer.http` package instead of relying on the deprecated one in XP
  framework core, retaining BC.
  (@thekid)
* Refactored API to use `util.Secret` class instead of the deprecated
  `security.SecureString` internally, retaining BC.
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
