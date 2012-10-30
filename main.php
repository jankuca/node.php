<?php

require __DIR__ . '/src/Application.php';
require __DIR__ . '/src/EventLoop.php';


function __main__() {
  global $loop;

  $argv = $_SERVER['argv'];

  // composer autoloader if available
  if (strpos($argv[0], 'vendor/') !== false) {
    require preg_replace('~vendor/.*$~', 'vendor/autoload.php', $argv[0]);
  }

  if (empty($argv[1])) {
    throw new Exception('No user script specified');
  }

  // object model
  $loop = new \Node\EventLoop();
  $app = new \Node\Application($loop);

  // run!
  $app->main($argv[1]);
}


function setTimeout($callback, $timeout) {
  global $loop;

  $loop->setTimeout($callback, $timeout);
}


function console_log() {
  echo call_user_func_array('sprintf', func_get_args()) . "\n";
}


__main__();
