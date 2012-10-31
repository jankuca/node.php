<?php

namespace Node;


class HTTPServer extends EventEmitter implements IStream {
  protected $fd;


  public function listen($port, $host = '0.0.0.0') {
    global $process;

    $addr = 'tcp://' . $host . ':' . $port;
    $this->fd = stream_socket_server($addr, $errno, $errstr);
    if ($this->fd === false) {
      throw new \Exception('Failed to start the server');
    }

    $process->addSocket($this);
  }


  public function getFD() {
    return $this->fd;
  }

  public function getMode() {
    return 'r';
  }


  public function read() {
    global $process;

    $start = microtime(true);
    $fd = stream_socket_accept($this->fd, 0);
    if ($fd !== false) {
      $req = new HTTPRequest($fd, 'r');
      $res = new HTTPResponse($fd, 'w');

      $process->addSocket($req);

      $self = $this;
      $req->on('head', function () use ($self, $req, $res) {
        $self->emit('request', $req, $res);
      });
      $res->on('end', function () use ($start) {
        $now = microtime(true);
        console_log('response end - request start = ' . ($now - $start) . ' ms');
      });
    }
  }

  public function close() {
    fclose($this->fd);
    $this->emit('close');
  }
}
