<?php

namespace Node;

class EventEmitter {
  private $__listeners__ = array();
  private $__max_listeners__ = 10;


  // public only for PHP 5.3, closures don't have contexts
  public function emit($type) {
    global $process;

    $args = array_slice(func_get_args(), 1);

    if (!empty($this->__listeners__[$type])) {
      $listeners = $this->__listeners__[$type];
      foreach ($listeners as $listener) {
        $args_single = array_merge(array($listener, 0), $args);
        call_user_func_array(array($process, 'setTimeout'), $args_single);
      }
    }
  }


  public function on($type, $listener) {
    if (!isset($this->__listeners__[$type])) {
      $this->__listeners__[$type] = array();
    }
    if (count($this->__listeners__[$type]) !== $this->__max_listeners__) {
      $this->__listeners__[$type][] = $listener;
    }
  }

  public function once($type, $listener) {
    $self = $this;

    $this->on($type, function () use ($type, $listener, $self) {
      $args = func_get_args();
      call_user_func_array($listener, $args);
      $self->removeListener($type, $listener);
    });
  }


  public function removeListener($type, $listener) {
    if (isset($this->__listeners__[$type])) {
      $listeners = &$this->__listeners__[$type];
      foreach ($listeners as $i => $fn) {
        if ($fn === $listener) {
          unset($listeners[$i]);
        }
      }
    }
  }

}
