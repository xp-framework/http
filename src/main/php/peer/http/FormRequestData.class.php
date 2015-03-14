<?php namespace peer\http;

use io\streams\MemoryInputStream;

/**
 * Build an HttpRequest w/ embedded multipart/form-data
 *
 * Example:
 * <code>
 *   $request= $conn->create(new HttpRequest());
 *   $request->setMethod(HttpConstants::POST);
 *   $request->setParameters((new FormRequestData())
 *     ->withPart(new FormData('key', 'value'))
 *     ->withPart(new FormData('comment.txt', $contents, 'text/plain', 'utf-8'))
 *   );
 * </code>
 *
 * @see   xp://peer.http.HttpConnection
 * @see   xp://peer.http.HttpRequest
 * @see   xp://peer.http.FormData
 * @test  xp://net.xp_framework.unittest.peer.http.FormRequestDataTest
 */
class FormRequestData extends \lang\Object implements Body {
  const CRLF= "\r\n";
  private $payload, $boundary;

  /**
   * Constructor
   *
   * @param   peer.http.FormData[] $parts
   */
  public function __construct($parts= []) {
    $this->boundary= '__--boundary-'.uniqid(time()).'--__';
    $this->payload= '';
    foreach ($parts as $part) {
      $this->addPart($part);
    }
  }
  
  /** @return string */
  public function boundary() { return $this->boundary; }
  
  /**
   * Add form part
   *
   * @param   peer.http.FormData $part
   * @return  peer.http.FormData
   */
  public function addPart(FormData $part) {
    $this->payload.= '--'.$this->boundary.self::CRLF.$part->getData().self::CRLF;
    return $part;
  }

  /**
   * Add form part - fluent interface
   *
   * @param   peer.http.FormData $part
   * @return  self this
   */
  public function withPart(FormData $part) {
    $this->payload.= '--'.$this->boundary.self::CRLF.$part->getData().self::CRLF;
    return $this;
  }


  /** @return [:var] */
  public function headers() {
    return [
      'Content-Type'   => ['multipart/form-data; boundary='.$this->boundary],
      'Content-Length' => [strlen($this->payload) + strlen($this->boundary) + 2 + 2 + 2]
    ];
  }
  
  /** @return io.streams.InputStream */
  public function stream() {
    return new MemoryInputStream($this->payload.'--'.$this->boundary.'--'.self::CRLF);
  }
}
