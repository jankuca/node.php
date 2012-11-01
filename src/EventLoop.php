<?php

namespace Node;

require_once __DIR__ . '/Timer.php';


class EventLoop {
  protected $timers = array();
  protected $streams = array();
  protected $curl_requests = array();
  protected $curl_request_handles = array();
  protected $child_processes = array();

  private $curl_multi_handle = null;

  private $curl_request_count = 0;
  private $timer_count = 0;


  public function run() {
    while (true) {
      $has_work = !!(
        count($this->timers) +
        count($this->streams) +
        count($this->curl_requests) +
        count($this->child_processes)
      );

      if (!$has_work) {
        break;
      }

      $now = microtime(true) * 1000;

      $timer = $this->getClosestTimer();
      if ($timer && $timer->dispatch_at <= $now) {
        $this->dispatchNonpositiveTimers();
      }

      $select_timeout = 10000000; // 10 seconds
      if ($timer) {
        $select_timeout = 10;
      }
      $this->handleStreams($select_timeout);

      $this->handleCURLRequests();

      $this->handleChildProcesses();

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
    $id = $stream->id;
    $mode = $stream->getMode();

    $this->streams[$id] = $stream;

    $streams = &$this->streams;
    $stream->once('close', function () use ($id, &$streams) {
      unset($streams[$id]);
    });
  }


  public function addCURLRequest($request) {
    if (!$this->curl_multi_handle) {
      $this->curl_multi_handle = curl_multi_init();
    }

    $index = $this->curl_request_count++;
    $handle = $request->getHandle();

    $this->curl_requests[$index] = $request;
    $this->curl_request_handles[$index] = $handle;

    curl_multi_add_handle($this->curl_multi_handle, $handle);
  }


  public function addChildProcess($child_process) {
    $pid = $child_process->pid;
    $this->child_processes[$pid] = $child_process;
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


  protected function handleCURLRequests() {
    if (count($this->curl_requests) !== 0) {
      $multi_handle = $this->curl_multi_handle;
      curl_multi_exec($multi_handle, $active);

      while ($info = curl_multi_info_read($multi_handle)) {
        $index = array_search($info['handle'], $this->curl_request_handles);
        $request = $this->curl_requests[$index];

        $status = curl_getinfo($info['handle'], CURLINFO_HTTP_CODE);
        $request->status($status);

        $chunk = curl_multi_getcontent($info['handle']);
        if (!is_null($chunk)) {
          $request->data($chunk);
        }
        $request->end();

        curl_multi_remove_handle($multi_handle, $info['handle']);

        unset($this->curl_requests[$index]);
        unset($this->curl_request_handles[$index]);
      }
    }
  }


  protected function handleChildProcesses() {
    foreach ($this->child_processes as $p) {
      $status = proc_get_status($p->getHandle());
      if (!$status['running']) {
        unset($this->child_processes[$p->pid]);
        $p->onexit($status['exitcode']);
      }
    }
  }

}
