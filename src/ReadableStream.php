<?php

namespace Node;

class ReadableStream extends Stream {

  public function read() {
    $handle = $this->getHandle();

    if (feof($handle)) {
      $this->close();
      return null;
    }

    $data = fread($handle, 4096);
    return $data;
  }

}
