<?php

namespace Node;

class WritableStream extends Stream {

  public function __construct($handle) {
    parent::__construct($handle, 'w');
  }


  public function resume() {
    $this->emit('resume');
  }


  public function write($data) {
    $handle = $this->handle();
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
