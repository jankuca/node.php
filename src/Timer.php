<?php

namespace NodePHP;

class Timer {
  protected $callback;
  public $dispatch_at;

  public function __construct($id, $dispatch_at) {
    $this->id = $id;
    $this->dispatch_at = $dispatch_at;
  }

  public function setCallback($callback) {
    $this->callback = $callback;
  }

  public function dispatch() {
    call_user_func($this->callback);
  }
}
