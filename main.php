<?php

function __autoload($class_name) {
  $parts = explode('\\', $class_name);
  if ($parts[0] === 'NodePHP') {
    $class_name = implode('/', array_slice($parts, 1));
    require_once('.' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $class_name . '.php');
  }
}


$GLOBALS['loop'] = new \NodePHP\EventLoop();
$app = new \NodePHP\Application($loop);


function setTimeout($callback, $timeout) {
  $GLOBALS['loop']->setTimeout($callback, $timeout);
}

function console_log($pattern) {
  echo call_user_func_array('sprintf', func_get_args()) . "\n";
}

$args = array_slice($_SERVER['argv'], 1);
call_user_func_array(array($app, 'main'), $args);
