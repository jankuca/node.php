<?php

$GLOBALS['loop'] = new \Node\EventLoop();
$app = new \Node\Application($loop);


function setTimeout($callback, $timeout) {
  $GLOBALS['loop']->setTimeout($callback, $timeout);
}

function console_log($pattern) {
  echo call_user_func_array('sprintf', func_get_args()) . "\n";
}


$args = array_slice($_SERVER['argv'], 1);
call_user_func_array(array($app, 'main'), $args);
