<?php

namespace Node;

class HTTPResponse extends WritableStream {
  public $status = 200;

  protected $head_sent = false;


  public function writeHead($status, $headers) {
    $this->status = $status;

    $header_lines = array_map(function ($value) {
      return key($header_lines) . ": " . $value;
    }, $headers);

    parent::write("HTTP/1.1 " . $status . " OK\r\n");
    parent::write(implode("\r\n", $header_lines) . "\r\n");

    $this->head_sent = true;
  }

  public function write($data) {
    if (!$this->head_sent) {
      $this->writeHead($this->status, array());
    }

    parent::write($data);
    //console_log('OUT: %s', $data);
  }


  public function end() {
    $this->close();
  }
}
