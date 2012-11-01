<?php

namespace Node;

require_once __DIR__ . '/HTTPClient.php';
require_once __DIR__ . '/HTTPServer.php';


class HTTP {

  static protected $default_options = array(
    'hostname' => 'localhost',
    'port' => 80,
    'method' => 'GET',
    'path' => '/',
    'headers' => array(),
    'auth' => null
  );


  static public function createServer($request_listener = null) {
    $server = new HTTPServer();

    if ($request_listener) {
      $server->on('request', $request_listener);
    }

    return $server;
  }


  static public function request($options, $response_listener = null) {
    $client = new HTTPClient();

    $options = array_merge(self::$default_options, $options);
    if (is_null($options['headers'])) {
      $options['headers'] = array();
    }

    $req = $client->request($options);

    if ($response_listener) {
      $req->once('response', $response_listener);
    }

    return $req;
  }


  static public function get($options, $response_listener = null) {
    $options['method'] = 'GET';

    $req = self::request($options, $response_listener);
    $req->end();

    return $req;
  }

}
