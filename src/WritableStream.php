<?php

namespace Node;

class WritableStream extends Stream {

  public function write($data) {
    $handle = $this->getHandle();
    if (!is_resource($handle)) {
      $this->emit('error', $err);
      return;
    }

    if (feof($handle)) {
      return false;
    }

    fwrite($handle, $data);
  }

}
