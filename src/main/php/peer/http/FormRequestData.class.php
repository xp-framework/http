<?php namespace peer\http;

/**
 * Build an HttpRequest w/ embedded multipart/form-data
 *
 * Example:
 * ```php
 * $request= $conn->create(new HttpRequest());
 * $request->setMethod(HttpConstants::POST);
 * $request->setParameters(create(new FormRequestData())
 *   ->withPart(new FormData('key', 'value'))
 *   ->withPart(new FormData('comment.txt', $contents, 'text/plain', 'utf-8'))
 * );
 * ```
 *
 * @see   xp://peer.http.HttpConnection
 * @see   xp://peer.http.HttpRequest
 * @see   xp://peer.http.FormData
 * @test  xp://net.xp_framework.unittest.peer.http.FormRequestDataTest
 */
class FormRequestData extends RequestData {
  const CRLF    = "\r\n";

  protected
    $parts      = [],
    $boundary   = null;

  /**
   * Constructor
   *
   * @param   peer.http.FormData[] $parts default array()
   */
  public function __construct($parts= []) {
    $this->boundary= '__--boundary-'.uniqid(time()).'--__';
    foreach ($parts as $part) {
      $this->addPart($part);
    }
  }

  /**
   * Set boundary
   *
   * @param   string $boundary
   * @return  string
   */
  public function withBoundary($boundary) {
    $this->boundary= $boundary;
    return $this;
  }    
  
  /**
   * Retrieve boundary
   *
   * @return  string
   */
  public function getBoundary() {
    return $this->boundary;
  }    
  
  /**
   * Add form part
   *
   * @param   peer.http.FormData $item
   * @return  peer.http.FormData
   */
  public function addPart(FormData $item) {
    $this->parts[]= $item;
    return $item;
  }

  /**
   * Add form part - fluent interface
   *
   * @param   peer.http.FormData $item
   * @return  self this
   */
  public function withPart(FormData $item) {
    $this->parts[]= $item;
    return $this;
  }
  
  /**
   * Retrieve headers to be set
   *
   * @return  peer.http.Header[]
   */
  public function getHeaders() {
    $headers= parent::getHeaders();
    $headers[]= new Header('Content-Type', 'multipart/form-data; boundary='.$this->boundary);
    return $headers;
  }
  
  /**
   * Retrieve data for request
   *
   * @return  string
   */
  public function getData() {
    $ret= '--'.$this->boundary;
    foreach ($this->parts as $part) {
      $ret.=  self::CRLF.$part->getData().self::CRLF.'--'.$this->boundary;
    }
    return $ret.'--'.self::CRLF;
  }
}