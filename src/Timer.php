<?php

namespace Node;

class Timer {
  public $dispatch_at;

  protected $args = array();
  protected $callback;


  public function __construct($id, $dispatch_at) {
    $this->id = $id;
    $this->dispatch_at = $dispatch_at;
  }

  public function setArguments($args) {
    $this->args = $args;
  }

  public function setCallback($callback) {
    $this->callback = $callback;
  }

  public function dispatch() {
    call_user_func_array($this->callback, $this->args);
  }
}
