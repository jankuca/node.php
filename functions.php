<?php


function setTimeout($callback, $timeout) {
  global $process;

  $process->setTimeout($callback, $timeout);
}


function console_log() {
  echo call_user_func_array('sprintf', func_get_args()) . "\n";
}
