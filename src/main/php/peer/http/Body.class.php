<?php namespace peer\http;

interface Body {

  /** @return [:var] */
  public function headers();

  /** @return io.streams.InputStream */
  public function stream();

}