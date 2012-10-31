<?php

namespace Node;

abstract class Stream extends EventEmitter implements IStream {
  public $id;

  protected $mode = 0;
  protected $handle;


  public function __construct($handle, $mode = 'r') {
    $this->handle = $handle;
    $this->mode = $mode;

    $this->id = microtime(true) . '.' . rand(1000, 9999);

    stream_set_blocking($handle, false);
  }

  public function getHandle() {
    return $this->handle;
  }

  public function getMode() {
    return $this->mode;
  }


  public function close() {
    if (is_resource($this->handle)) {
      fclose($this->handle);
    }

    $this->emit('close');
  }
}
