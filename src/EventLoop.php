<?php

namespace Node;

require_once __DIR__ . '/Timer.php';


class EventLoop {
  protected $events = array();
  protected $timers = array();
  protected $sockets = array();

  private $timer_count = 0;


  public function run() {
    while (true) {
      $has_work = count($this->events) + count($this->timers) + count($this->sockets);
      if (!$has_work) {
        break;
      }

      $now = microtime(true);

      $timer = $this->getClosestTimer();
      if ($timer && $timer->dispatch_at <= $now) {
        unset($this->timers[$timer->id]);
        $timer->dispatch();
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


  public function addSocket($socket) {
    $mode = $socket->getMode();

    $this->sockets[$socket->id] = $socket;

    $sockets = &$this->sockets;
    $socket->on('close', function () use ($socket, &$sockets) {
      unset($sockets[$socket->id]);
    });
  }


  protected function getClosestTimer() {
    $timers = $this->timers;
    if (count($timers) === 0) {
      return null;
    }

    usort($timers, function ($a, $b) {
      $diff = $a->dispatch_at - $b->dispatch_at;
      if ($diff === 0) {
        $diff = $a->id - $b->id;
      }
      return $diff;
    });

    return $timers[0];
  }


  protected function handleStreams($select_timeout) {
    $fds = $this->getFDsByMode();

    //console_log('timeout: %d', $select_timeout);
    $ready = $this->selectStream($fds, $select_timeout);
    if ($ready) {
      foreach ($fds['r'] as $fd) {
        $socket_id = array_search($fd, $fds['all'], true);
        $socket = $this->sockets[$socket_id];
        //echo (string) $fd . " : " . get_class($socket) . "\n";
        $socket->read();
      }

      foreach ($fds['w'] as $fd) {
        $socket_id = array_search($fd, $fds['all'], true);
        $socket = $this->sockets[$socket_id];
        $socket->resume();
      }

      foreach ($fds['e'] as $fd) {
        $socket_id = array_search($fd, $fds['all'], true);
        $socket = $this->sockets[$socket_id];
        $socket->except();
      }
    }
  }


  protected function getFDsByMode() {
    $streams = array(
      'r' => array(),
      'w' => array(),
      'e' => array(),
      'all' => array()
    );

    foreach ($this->sockets as $i => $socket) {
      if (get_resource_type($socket->getFD()) !== 'stream') {
        $socket->close();
        unset($this->sockets[$i]);
      } else {
        $mode = $socket->getMode();
        $streams[$mode][$socket->id] = $socket->getFD();
        $streams['all'][$socket->id] = $socket->getFD();
      }
    }

    return $streams;
  }

  protected function selectStream($fds, $timeout = 0) {
    if (count($fds['r']) + count($fds['w']) + count($fds['e']) !== 0) {
      return stream_select($fds['r'], $fds['w'], $fds['e'], 0, $timeout);
    }
    return 0;
  }

}
