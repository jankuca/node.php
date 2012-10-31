<?php

namespace Node;

require_once __DIR__ . '/Timer.php';


class EventLoop {
  protected $events = array();
  protected $timers = array();
  protected $streams = array();

  private $timer_count = 0;


  public function run() {
    while (true) {
      $has_work = count($this->events) + count($this->timers) + count($this->streams);
      if (!$has_work) {
        break;
      }

      $now = microtime(true) * 1000;

      $timer = $this->getClosestTimer();
      if ($timer && $timer->dispatch_at <= $now) {
        $this->dispatchNonpositiveTimers();
      }

      $event = array_shift($this->events);
      if ($event) {
        $event->dispatch();
      }

      $select_timeout = 10000000; // 10 seconds
      if ($timer) {
        if ($timer->dispatch_at > $now) {
          $select_timeout = 1000 * ($timer->dispatch_at - $now);
        } else {
          $select_timeout = 0;
        }
      }
      $this->handleStreams($select_timeout);

      // do not halt the CPU
      usleep(1000);
    }
  }


  public function setTimeout($callback, $timeout) {
    $id = $this->timer_count++;
    $dispatch_at = microtime(true) * 1000 + $timeout;
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


  public function addStream($stream) {
    $mode = $stream->getMode();

    $this->streams[$stream->id] = $stream;

    $streams = &$this->streams;
    $stream->once('close', function () use ($stream, &$streams) {
      unset($streams[$stream->id]);
    });
  }


  protected function getClosestTimer() {
    $timers = $this->timers;
    if (count($timers) === 0) {
      return null;
    }

    usort($timers, function ($a, $b) {
      $diff = $b->dispatch_at - $a->dispatch_at;
      if ($diff === 0) {
        $diff = $b->id - $a->id;
      }
      return $diff;
    });

    return $timers[0];
  }


  protected function dispatchNonpositiveTimers() {
    $timers = $this->timers;
    if (count($timers) !== 0) {
      $now = microtime(true) * 1000;
      $isNonpositive = function ($timer) use ($now) {
        return ($timer->dispatch_at <= $now);
      };

      $nonpositive_timers = array_filter($timers, $isNonpositive);
      foreach ($nonpositive_timers as $timer) {
        unset($this->timers[$timer->id]);
        $timer->dispatch();
      }
    }
  }


  protected function handleStreams($select_timeout) {
    $handles = $this->getStreamHandlesByMode();

    //console_log('timeout: %d', $select_timeout);
    $ready = $this->selectStream($handles, $select_timeout);
    if ($ready) {
      foreach ($handles['r'] as $handle) {
        $socket_id = array_search($handle, $handles['all'], true);
        $stream = $this->streams[$socket_id];
        //echo (string) $handle . " : " . get_class($stream) . "\n";
        $stream->read();
      }

      foreach ($handles['w'] as $handle) {
        $socket_id = array_search($handle, $handles['all'], true);
        $stream = $this->streams[$socket_id];
        $stream->resume();
      }

      foreach ($handles['e'] as $handle) {
        $socket_id = array_search($handle, $handles['all'], true);
        $stream = $this->streams[$socket_id];
        $stream->except();
      }
    }
  }


  protected function getStreamHandlesByMode() {
    $streams = array(
      'r' => array(),
      'w' => array(),
      'e' => array(),
      'all' => array()
    );

    foreach ($this->streams as $i => $stream) {
      if (get_resource_type($stream->getHandle()) !== 'stream') {
        $stream->close();
        unset($this->streams[$i]);
      } else {
        $mode = $stream->getMode();
        $streams[$mode][$stream->id] = $stream->getHandle();
        $streams['all'][$stream->id] = $stream->getHandle();
      }
    }

    return $streams;
  }

  protected function selectStream($handles, $timeout = 0) {
    if (count($handles['r']) + count($handles['w']) + count($handles['e']) !== 0) {
      return stream_select($handles['r'], $handles['w'], $handles['e'], 0, $timeout);
    }
    return 0;
  }

}
