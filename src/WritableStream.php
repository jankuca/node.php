<?php

namespace Node;

class WritableStream extends Stream {
  protected $buffered = false;


  public function __construct($handle) {
    parent::__construct($handle, 'w');
  }


  public function resume() {
    $this->emit('resume');
  }


  public function write($data) {
    $handle = $this->handle;
    if (!is_resource($handle)) {
      $this->emit('error', $err);
      return;
    }

    if (feof($handle)) {
      return false;
    }

    $total = strlen($data);
    $written = fwrite($handle, $data);

    $this->drained = ($written < $total);
    if ($this->drained) {
      $data = substr($data, $written);
      $this->once('resume', function () use (&$data) {
        $this->write($data);
      });
      $this->emit('drain');
    }
  }


  public function end() {
    if ($this->buffered) {
      $self = $this;
      $this->once('resume', function () use ($self) {
        fclose($this->handle);
        $this->emit('end');
      });
    } else {
      fclose($this->handle);
      $this->emit('end');
    }
  }

}
