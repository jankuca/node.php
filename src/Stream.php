<?php

namespace Node;

abstract class Stream extends EventEmitter implements IStream {
  public $id;

  protected $mode = 0;
  protected $fd;


  public function __construct($fd, $mode) {
    $this->fd = $fd;
    $this->mode = $mode;

    $this->id = microtime(true) . '.' . rand(1000, 9999);

    stream_set_blocking($fd, false);
  }

  public function getFD() {
    return $this->fd;
  }

  public function getMode() {
    return $this->mode;
  }


  public function close() {
    if (get_resource_type($this->fd) === 'stream') {
      fclose($this->fd);
    }
    $this->emit('close');
  }
}
