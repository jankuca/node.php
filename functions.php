<?php


function setTimeout($callback, $timeout) {
  global $process;

  return $process->setTimeout($callback, $timeout);
}


function clearTimeout($handle) {
  global $process;

  $process->clearTimeout($handle);
}


function console_log() {
  echo call_user_func_array('sprintf', func_get_args()) . "\n";
}
