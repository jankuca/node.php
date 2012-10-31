<?php

namespace Node;


class HTTPServer extends EventEmitter implements IStream {
  protected $handle;


  public function listen($port, $host = '0.0.0.0') {
    global $process;

    $addr = 'tcp://' . $host . ':' . $port;
    $this->handle = stream_socket_server($addr, $errno, $errstr);
    if ($this->handle === false) {
      throw new \Exception('Failed to start the server');
    }

    $process->addStream($this);
  }


  public function getHandle() {
    return $this->handle;
  }

  public function getMode() {
    return 'r';
  }


  public function read() {
    global $process;

    $start = microtime(true);
    $client_handle = stream_socket_accept($this->handle, 0);
    if ($client_handle !== false) {
      $req = new HTTPRequest($client_handle, 'r');
      $res = new HTTPResponse($client_handle, 'w');

      $process->addStream($req);

      $self = $this;
      $req->once('head', function () use ($self, $req, $res) {
        $self->emit('request', $req, $res);
      });
      //$res->once('close', function () use ($start) {
      //  $now = microtime(true);
      //  console_log('response end - request start = ' . ($now - $start) . ' ms');
      //});
    }
  }

  public function close() {
    fclose($this->handle);
    $this->emit('close');
  }
}
