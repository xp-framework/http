<?php namespace peer\http;

/**
 * Use RequestData to pass request data directly to body
 *
 * @see   xp://peer.http.HttpRequest#setParameters
 */
class RequestData extends \lang\Object {
  public $data;

  /**
   * Constructor
   *
   * @param   string $buf
   */
  public function __construct($buf) {
    $this->data= $buf;
  }

  /**
   * Return list of HTTP headers to be set on
   * behalf of the data
   *
   * @return  peer.Header[]
   */
  public function getHeaders() {
    return array();
  }
  
  /**
   * Retrieve data
   *
   * @return  string
   */
  public function getData() {
    return $this->data;
  }
}
