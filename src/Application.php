<?php

namespace NodePHP;

class Application {
  protected $loop;

  public function __construct($loop) {
    $this->loop = $loop;
  }

  public function main($main_module_filename) {
    $this->loop->setTimeout(function () use ($main_module_filename) {
      require($main_module_filename);
    }, 0);

    $this->loop->run();
  }
};
