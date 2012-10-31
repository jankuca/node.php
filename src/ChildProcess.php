<?php

namespace Node;

class ChildProcess extends EventEmitter {
  public $cmd;

  public $pid = 0;
  public $status = 0;

  public $stdin;
  public $stdout;
  public $stderr;

  protected $handle;


  public function __construct($cmd) {
    $this->cmd = $cmd;
  }


  public function getHandle() {
    return $this->handle;
  }


  public function run() {
    $descs = array(
      array('pipe', 'r'),
      array('pipe', 'w'),
      array('pipe', 'w')
    );

    $handle = proc_open($this->cmd, $descs, $handles);
    if (!is_resource($handle)) {
      $err = new Exception('Failed to spawn the child process');
      $this->emit('error', $err);
      return;
    }

    $this->registerProcess($handle);
    $this->registerStreams($handles);
  }


  public function onexit($code) {
    $stdin_handle = $this->stdin->getHandle();
    $stdout_handle = $this->stdout->getHandle();
    $stderr_handle = $this->stderr->getHandle();

    if (is_resource($stdin_handle)) {
      fclose($stdin_handle);
    }
    if (is_resource($stdout_handle)) {
      fclose($stdout_handle);
    }
    if (is_resource($stderr_handle)) {
      fclose($stderr_handle);
    }

    $this->emit('exit', $code);
  }


  protected function registerProcess($handle) {
    global $process;

    $status = proc_get_status($handle);

    $this->handle = $handle;
    $this->pid = $status['pid'];
    $process->addChildProcess($this);
  }

  protected function registerStreams($handles) {
    global $process;

    $this->stdin = new WritableStream($handles[0]);
    $this->stdout = new ReadableStream($handles[1]);
    $this->stderr = new ReadableStream($handles[2]);

    $process->addStream($this->stdin);
    $process->addStream($this->stdout);
    $process->addStream($this->stderr);
  }



  static public function exec($cmd) {
    $process = new ChildProcess($cmd);
    $process->run();
    return $process;
  }


  static public function spawn($executable, $args = array()) {
    $cmd = escapeshellcmd($executable);
    foreach ($args as $arg) {
      $cmd .= ' ' . escapeshellarg($arg);
    }

    $process = new ChildProcess($cmd);
    $process->run();
    return $process;
  }

}
