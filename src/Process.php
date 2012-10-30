<?php

namespace Node;

require __DIR__ . '/EventLoop.php';


final class Process {
  public $argv = array();
  public $argc = 0;
  public $env = array();

  private $event_loop;


  public function __construct($env) {
    $this->env = $env;
    $this->argv = $this->env['argv'];
    $this->argc = count($this->argv);

    $this->event_loop = new EventLoop();
  }


  public function main() {
    // composer autoloader if available
    $file = $this->argv[0];
    if (strpos($file, 'vendor/') !== false) {
      $autoloader = preg_replace('~vendor/.*$~', 'vendor/autoload.php', $file);
      require $autoloader;
    }

    // main module
    if (empty($this->argv[1])) {
      throw new \Exception('No user script specified');
    }

    $this->event_loop->setTimeout(function ($main_module_filename) {
      require $main_module_filename;
    }, 0, $this->argv[1]);

    $this->event_loop->run();
  }


  public function setTimeout() {
    $args = func_get_args();
    call_user_func_array(array($this->event_loop, 'setTimeout'), $args);
  }

}
