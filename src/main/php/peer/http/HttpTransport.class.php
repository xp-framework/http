<?php namespace peer\http;

use peer\URL;
use lang\XPClass;
use lang\Closeable;
use lang\IllegalArgumentException;
use peer\http\proxy\EnvironmentSettings;
use peer\http\proxy\RegistrySettings;

/**
 * HTTP Transports base class
 *
 * @see   xp://peer.http.HttpConnection
 * @test  xp://peer.http.unittest.HttpTransportTest
 */
abstract class HttpTransport implements Closeable {
  protected static $transports= [];
  protected static $settings;
  protected $proxy= null;
  protected $cat= null;

  static function __static() {

    // Depending on what extension is available, choose a different implementation 
    // for SSL transport. CURL is the slower one, so favor SSLSockets.
    self::$transports['http']= XPClass::forName('peer.http.SocketHttpTransport');
    if (extension_loaded('openssl')) {
      self::$transports['https']= XPClass::forName('peer.http.SSLSocketHttpTransport');
    } else if (extension_loaded('curl')) {
      self::$transports['https']= XPClass::forName('peer.http.CurlHttpTransport');
    }

    // Detect system proxy
    self::$settings= new EnvironmentSettings();
    if (!self::$settings->detected() && strncasecmp(PHP_OS, 'Win', 3) === 0 && extension_loaded('com')) {
      self::$settings= new RegistrySettings();
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
    return nameof($this);
  }
  
  /**
   * Register transport implementation for a specific scheme
   *
   * @param   string scheme
   * @param   lang.XPClass<peer.http.HttpTransport> class
   */
  public static function register($scheme, XPClass $class) {
    if (!$class->isSubclassOf('peer.http.HttpTransport')) {
      throw new IllegalArgumentException(sprintf(
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
      throw new IllegalArgumentException('Scheme "'.$scheme.'" unsupported');
    }

    $transport= self::$transports[$scheme]->newInstance($url, $arg);
    $transport->setProxy(self::$settings->proxy($scheme));
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

  /** @return void */
  public function close() {
    // NOOP
  }
}
