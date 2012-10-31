<?php

namespace Node;

class HTTPRequest extends ReadableStream {
  public $method;
  public $url;
  public $http_version;
  public $headers = array();

  protected $head_parsed = false;

  private $buffer = '';


  public function read() {
    $data = parent::read();
    if ($data !== null) {
      $this->parseChunk($data);
    }
    return $data;
  }

  protected function parseChunk($data) {
    if (!$this->head_parsed) {
      $parts = explode("\r\n\r\n", $data);
      $head = $this->buffer . $parts[0];

      $lines = explode("\r\n", $head);
      $finished_headers = isset($parts[1]) ? count($lines) : count($lines) - 1;

      if (count($this->headers) === 0) {
        $line_parts = explode(' ', $lines[0]);
        $this->method = $line_parts[1];
        $this->url = $line_parts[1];
        $this->http_version = $line_parts[2];
      }

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
        $this->emit('data', $parts[1]);
      } else {
        $this->buffer = end($lines);
      }
    } else {
      $this->emit('data', $data);
    }
  }
}
