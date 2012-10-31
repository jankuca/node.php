<?php

namespace Node;

class FS {

  static function readdir($path, $callback) {
    $p = ChildProcess::spawn('ls', array('-a', $path));

    $files = array();
    $buffer = '';

    $errstr = '';
    $p->stderr->on('data', function ($chunk) use (&$errstr) {
      $errstr .= $chunk;
    });

    $p->stdout->on('data', function ($chunk) use (&$files, &$buffer) {
      $chunk = $buffer . $chunk;
      $lines = explode("\n", $chunk);

      if (end($lines) === '') {
        $buffer = array_pop($lines);
      }

      foreach ($lines as $line) {
        array_push($files, $line);
      }
    });

    $p->once('exit', function ($code) use (&$files, $callback, &$errstr) {
      if ($code !== 0) {
        $err = new \Exception(trim($errstr));
        $callback($err, null);
        return;
      }

      array_splice($files, 0, 2);
      sort($files);
      $callback(null, $files);
    });
  }

}
