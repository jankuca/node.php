<?php

namespace Node;

require_once __DIR__ . '/HTTPServer.php';


class HTTP {

  static public function createServer($request_listener) {
    $server = new HTTPServer();

    if ($request_listener) {
      $server->on('request', $request_listener);
    }

    return $server;
  }

}
