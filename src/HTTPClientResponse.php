<?php

namespace Node;

class HTTPClientResponse extends EventEmitter {
  public $status;
  public $headers = array();

  protected $head_parsed = false;

  private $buffer = '';


  public function data($data) {
    if (!$this->head_parsed) {
      $parts = explode("\r\n\r\n", $data);
      $head = $this->buffer . $parts[0];

      $lines = explode("\r\n", $head);
      $finished_headers = isset($parts[1]) ? count($lines) : count($lines) - 1;

      $i = count($this->headers) === 0 ? 1 : 0;

      for (; $i < $finished_headers; ++$i) {
        list($key, $value) = explode(': ', $lines[$i], 2);
        $key = strtolower($key);
        $this->headers[$key] = $value;
        $this->emit('header', $key, $value);
      }

      if (isset($parts[1])) {
        $this->buffer = '';
        $this->head_parsed = true;
        $this->emit('head');

        $self = $this;
        $chunk = $parts[1];
        setTimeout(function () use ($self, &$chunk) {
          $self->emit('data', $chunk);
        }, 10000);
      } else {
        $this->buffer = end($lines);
      }
    } else {
      $this->emit('data', $data);
    }
  }

}
