<?php

namespace Node;

require_once __DIR__ . '/Stream.php';


class HTTPResponse extends Stream {
  public $status = 200;

  protected $head_sent = false;


  public function writeHead($status, $headers) {
    $this->status = $status;

    $header_lines = array_map(function ($value) {
      return key($header_lines) . ": " . $value;
    }, $headers);

    $fd = $this->getFD();
    fwrite($fd, "HTTP/1.1 " . $status . " OK\r\n");
    fwrite($fd, implode("\r\n", $header_lines) . "\r\n");

    $this->head_sent = true;
  }

  public function write($data) {
    $fd = $this->getFD();

    if (get_resource_type($fd) !== 'stream' || feof($fd)) {
      return false;
    }

    if (!$this->head_sent) {
      $this->writeHead($this->status, array());
    }

    fwrite($fd, $data);
    //console_log('OUT: %s', $data);
  }


  public function end() {
    fclose($this->getFD());
    $this->emit('end');
    $this->emit('close');
  }
}
