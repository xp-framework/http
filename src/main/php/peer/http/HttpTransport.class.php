<?php namespace peer\http;

use peer\URL;
use lang\XPClass;

/**
 * HTTP Transports base class
 *
 * @see   xp://peer.http.HttpConnection
 * @test  xp://peer.http.unittest.HttpTransportTest
 */
abstract class HttpTransport extends \lang\Object {
  protected static $transports= [];
  protected $proxy= null;
  protected $cat= null;

  public static $PROXIES= [];
  
  static function __static() {
    static $settings= 'HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Internet Settings';
    static $environment= [
      'http_proxy'  => 'http',
      'https_proxy' => 'https',
      'HTTP_PROXY'  => 'http',
      'HTTPS_PROXY' => 'https',
      'all_proxy'   => '*'
    ];

    // Depending on what extension is available, choose a different implementation 
    // for SSL transport. CURL is the slower one, so favor SSLSockets.
    self::$transports['http']= XPClass::forName('peer.http.SocketHttpTransport');
    if (extension_loaded('openssl')) {
      self::$transports['https']= XPClass::forName('peer.http.SSLSocketHttpTransport');
    } else if (extension_loaded('curl')) {
      self::$transports['https']= XPClass::forName('peer.http.CurlHttpTransport');
    }

    // Detect system proxy via environment variables
    if ($no= getenv('no_proxy')) {
      $excludes= explode(',', $no);
    } else {
      $excludes= [];
    }
    foreach ($environment as $var => $proto) {
      if ($env= getenv($var)) {
        if (false === ($p= strpos($env, '://'))) {
          self::$PROXIES[$proto]= new HttpProxy($env, null, $excludes);
        } else {
          self::$PROXIES[$proto]= new HttpProxy(rtrim(substr($env, $p + 3), '/'), null, $excludes);
        }
      }
    }

    // Detect system proxy via IE settings on Windows
    if (!self::$PROXIES && strncasecmp(PHP_OS, 'Win', 3) === 0) {
      $c= new \Com('WScript.Shell');
      if ($c->regRead($settings.'\ProxyEnable')) {
        $proxy= $c->regRead($settings.'\ProxyServer');

        try {
          $excludes= explode(';', $c->regRead($settings.'\ProxyOverride'));
        } catch (\Exception $ignored) {
          $excludes= [];
        }

        if (strstr($proxy, ';')) {
          foreach (explode(';', $proxy) as $setting) {
            sscanf($setting, '%[^=]=%[^:]:%d', $proto, $host, $port);
            self::$PROXIES[$proto]= new HttpProxy($host, $port, $excludes);
          }
        } else {
          self::$PROXIES['*']= new HttpProxy($proxy, null, $excludes);
        }
      }
    }
  }
  
  /**
   * Constructor
   *
   * @param   peer.URL url
   * @param   string arg
   */
  abstract public function __construct(URL $url, $arg);

  /**
   * Set proxy
   *
   * @param   peer.http.HttpProxy proxy
   */
  public function setProxy(HttpProxy $proxy= null) {
    $this->proxy= $proxy;
  }

  /**
   * Sends a request via this proxy
   *
   * @param   peer.http.HttpRequest request
   * @param   int timeout default 60
   * @param   float connecttimeout default 2.0
   * @return  peer.http.HttpResponse response object
   */
  abstract public function send(HttpRequest $request, $timeout= 60, $connecttimeout= 2.0);
  
  /**
   * Creates a string representation of this object
   *
   * @return  string
   */
  public function toString() {
    return $this->getClassName();
  }
  
  /**
   * Register transport implementation for a specific scheme
   *
   * @param   string scheme
   * @param   lang.XPClass<peer.http.HttpTransport> class
   */
  public static function register($scheme, XPClass $class) {
    if (!$class->isSubclassOf('peer.http.HttpTransport')) {
      throw new \lang\IllegalArgumentException(sprintf(
        'Given argument must be lang.XPClass<peer.http.HttpTransport>, %s given',
        $class->toString()
      ));
    }
    self::$transports[$scheme]= $class;
  }

  /**
   * Get transport implementation for a specific URL
   *
   * @param   peer.URL url
   * @return  peer.http.HttpTransport
   * @throws  lang.IllegalArgumentException in case the scheme is not supported
   */
  public static function transportFor(URL $url) {
    sscanf($url->getScheme(), '%[^+]+%s', $scheme, $arg);
    if (!isset(self::$transports[$scheme])) {
      throw new \lang\IllegalArgumentException('Scheme "'.$scheme.'" unsupported');
    }

    $transport= self::$transports[$scheme]->newInstance($url, $arg);
    if (isset(self::$PROXIES[$scheme])) {
      $transport->setProxy(self::$PROXIES[$scheme]);
    } else if (isset(self::$PROXIES['*'])) {
      $transport->setProxy(self::$PROXIES['*']);
    }
    return $transport;
  }

  /**
   * Sets a logger category for debugging
   *
   * @param   util.log.LogCategory cat
   */
  public function setTrace($cat) {
    $this->cat= $cat;
  }
}
