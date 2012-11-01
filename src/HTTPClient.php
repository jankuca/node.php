<?php

namespace Node;

require __DIR__ . '/HTTPClientRequest.php';
require __DIR__ . '/HTTPClientResponse.php';


class HTTPClient {

  public function request($options) {
    if (!empty($options['auth'])) {
      $options['headers']['authorization'] = 'Basic ' . base64_encode($options['auth']);
    }

    $uri = 'http://' . $options['host'];
    if ($options['port'] !== 80) {
      $uri .= ':' . $options['port'];
    }
    $uri .= $options['path'];

    $req = new HTTPClientRequest($options['method'], $uri, $options['headers']);
    $this->registerCURLRequest($req);

    return $req;
  }


  protected function createHTTPContext($options) {
    $head = '';
    foreach ($options['headers'] as $key => $value) {
      $head .= mb_convert_case($key, MB_CASE_TITLE) . ': ';
      $head .= $value;
      $head .= "\r\n";
    }

    $ctx_options = array(
      'http' => array(
        'method' => $options['method'],
        'header' => $head,
        'user_agent' => 'node.php/http',
        'follow_location' => 1,
        'ignore_errors' => true
      )
    );

    $ctx = stream_context_create($ctx_options);
    return $ctx;
  }


  protected function registerCURLRequest($request) {
    global $process;

    $process->addCURLRequest($request);
  }


  protected function registerStreams($req, $res) {
    global $process;

    $process->addStream($req);
    $process->addStream($res);
  }

}
