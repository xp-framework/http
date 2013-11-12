HTTP protocol support for the XP Framework
========================================================================

Implements HTTP (HyperText Transfer Protocol) and provides a client
to interact with HTTP servers. The `HttpConnection` is the entry
point class.

Methods
-------
Different request methods are handled by `HttpConnection` class
methods as follows:

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
with ($c= new HttpConnection('http://xp-framework.net/')); {
  Console::writeLine($c->head()->toString());
}
```

Getting data
-----------

```php
with ($c= new HttpConnection('http://xp-framework.net/')); {
  $response= $c->get();
  Console::writeLine('Response: ', $response);
  
  while ($chunk= $response->readData()) {
    // ...
  }
}
```

SSL support
-----------
This API also supports SSL connections - based on the scheme given to
`HttpConnection`'s constructor the `HttpRequestFactory` class will create 
an SSL connection. This is transparent from the outside, the rest of the
calls are the same!

Example:

```php
$c= new HttpConnection('https://example.com/');
```

Note: SSL connections depend on either the PHP extension `curl` or `openssl`.
