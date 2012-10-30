<?php

namespace Node;

require __DIR__ . '/Timer.php';

class EventLoop {
  protected $events = array();
  protected $timers = array();

  private $timer_count = 0;


  public function run() {
    while (true) {
      $timer = $this->getNonpositiveTimer();
      if ($timer) {
        $timer->dispatch();
      }

      $event = array_shift($this->events);
      if ($event) {
        $event->dispatch();
      }

      // do not halt the CPU
      usleep(1000);
    }
  }


  public function setTimeout($callback, $timeout) {
    $id = $this->timer_count++;
    $dispatch_at = microtime(true) + $timeout / 1000;
    $args = array_slice(func_get_args(), 2);

    $timer = new Timer($id, $dispatch_at);
    $timer->setArguments($args);
    $timer->setCallback($callback);

    $this->timers[$id] = $timer;
    return $id;
  }

  public function clearTimeout($handle) {
    if (isset($this->timers[$handle])) {
      unset($this->timers[$handle]);
    }
  }


  protected function getNonpositiveTimer() {
    $now = microtime(true);
    $nonpositives = array_filter($this->timers, function ($timer) use (&$now) {
      return ($timer->dispatch_at <= $now);
    });

    if (count($nonpositives) === 0) {
      return null;
    }

    usort($nonpositives, function ($a, $b) {
      $diff = $a->dispatch_at - $b->dispatch_at;
      if ($diff === 0) {
        $diff = $a->id - $b->id;
      }
      return $diff;
    });

    $timer = $nonpositives[0];
    unset($this->timers[$timer->id]);

    return $timer;
  }
}
