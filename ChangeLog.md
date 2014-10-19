HTTP protocol support for the XP Framework ChangeLog
========================================================================

## ?.?.? / ????-??-??

* Work to support HTTP proxies transparently:
  - Detect and use system proxy settings per default (PR #3)
  - Forcing a direct connection possible using `setProxy(HttpProxy::NONE)`
  - Implement using "CONNECT" for tunneling HTTPS through proxy (PR #2)
  - Use "GET http://..." for HTTP through a proxy
  (@thekid)
* Heads up: Converted classes to PHP 5.3 namespaces - (@thekid)
