HTTP protocol support for the XP Framework
========================================================================

[![Build status on GitHub](https://github.com/xp-framework/http/workflows/Tests/badge.svg)](https://github.com/xp-framework/http/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-framework/http/version.png)](https://packagist.org/packages/xp-framework/http)

Implements HTTP (HyperText Transfer Protocol) and provides a client to interact with HTTP servers. The `HttpConnection` is the entry point class.

Methods
-------
Different request methods are handled by `HttpConnection` class methods as follows:

* GET - via `get()`
* POST - via `post()`
* HEAD - via `head()`
* PUT - via `put()`
* PATCH - via `patch()`
* DELETE - via `delete()`
* OPTIONS - via `options()`
* TRACE - via `trace()`

Other methods (e.g. `MKCOL` from WebDAV) are supported via `request()`.

Headers
-------
The following code will show the response headers for a HEAD request:

```php
use peer\http\HttpConnection;

$c= new HttpConnection('http://xp-framework.net/');
Console::writeLine($c->head());
```

Getting data
-----------

```php
with ($c= new HttpConnection('http://xp-framework.net/')); {
  $response= $c->get();
  Console::writeLine('Response: ', $response);
  
  $in= $response->in();
  while ($in->available()) {
    $bytes= $in->read();
  }
}
```

SSL support
-----------
This API also supports SSL connections - based on the scheme given to `HttpConnection`'s constructor the `HttpRequestFactory` class will create an SSL connection. This is transparent from the outside, the rest of the calls are the same!

Example:

```php
$c= new HttpConnection('https://example.com/');
```

Note: SSL connections depend on either the PHP extension `curl` or `openssl`.
