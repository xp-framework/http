<?php namespace peer\http;

use io\streams\MemoryInputStream;

/**
 * Use RequestData to pass request data directly to body
 *
 * @see   xp://peer.http.HttpRequest#withBody
 */
class RequestData extends \lang\Object implements Body {
  private $payload, $contentType;

  /**
   * Constructor
   *
   * @param   var $data Either a string or a map of key/value pairs
   * @param   string $contentType
   */
  public function __construct($data, $contentType= 'application/x-www-form-urlencoded') {
    if (is_array($data)) {
      $string= '';
      foreach ($data as $name => $value) {
        if (is_array($value)) {
          foreach ($value as $k => $v) {
            $string.= '&'.$name.'['.$k.']='.urlencode($v);
          }
        } else {
          $string.= '&'.$name.'='.urlencode($value);
        }
      }
      $this->payload= substr($string, 1);
    } else {
      $this->payload= (string)$data;
    }
    $this->contentType= $contentType;
  }

  /** @return [:var] */
  public function headers() {
    return [
      'Content-Type'   => [$this->contentType],
      'Content-Length' => [strlen($this->payload)]
    ];
  }

  /** @return io.streams.InputStream */
  public function stream() { return new MemoryInputStream($this->payload); }
}
