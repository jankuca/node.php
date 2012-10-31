<?php

namespace Node;

class ReadableStream extends Stream {

  public function __construct($handle) {
    parent::__construct($handle, 'r');
  }


  public function read() {
    $handle = $this->handle;

    if (feof($handle)) {
      $this->close();
      return null;
    }

    $data = fread($handle, 4096);
    return $data;
  }

}
