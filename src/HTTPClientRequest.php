<?php

namespace Node;

class HTTPClientRequest extends EventEmitter implements ICURLRequest {
  protected $curl_handle;

  protected $res;


  public function __construct($method, $uri, $headers) {
    $header_lines = array();
    foreach ($headers as $key => $value) {
      $header_lines[] = mb_convert_case($key, MB_CASE_TITLE) . ': ' . $value;
    }

    $curl_handle = curl_init();
    curl_setopt_array($curl_handle, array(
      CURLOPT_URL => $uri,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_HEADER => true,
      CURLOPT_FAILONERROR => false,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => $header_lines
    ));

    $this->curl_handle = $curl_handle;
  }


  public function getHandle() {
    return $this->curl_handle;
  }


  public function status($status) {
    $res = new HTTPClientResponse();
    $res->status = $status;

    $this->res = $res;

    $self = $this;
    $res->once('head', function () use ($self, $res) {
      $self->emit('response', $res);
    });
  }

  public function data($chunk) {
    $this->res->data($chunk);
  }

  public function end() {
  }

}
