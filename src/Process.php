<?php

namespace Node;

require_once __DIR__ . '/IStream.php';
require_once __DIR__ . '/ICURLRequest.php';

require_once __DIR__ . '/EventEmitter.php';
require_once __DIR__ . '/EventLoop.php';
require_once __DIR__ . '/Stream.php';
require_once __DIR__ . '/ReadableStream.php';
require_once __DIR__ . '/WritableStream.php';
require_once __DIR__ . '/HTTPRequest.php';
require_once __DIR__ . '/HTTPResponse.php';
require_once __DIR__ . '/ChildProcess.php';


final class Process extends EventEmitter {
  public $argv = array();
  public $argc = 0;
  public $env = array();

  private $event_loop;
  private $__uncaught_exception_listeners__ = array();


  public function __construct($env) {
    $this->env = $env;
    $this->argv = $this->env['argv'];
    $this->argc = count($this->argv);

    $this->event_loop = new EventLoop();
  }


  public function on($type, $listener) {
    if ($type === 'uncaughtException') {
      $this->__uncaught_exception_listeners__[] = $listener;
    } else {
      parent::on($type, $listener);
    }
  }


  public function emit($type) {
    if ($type === 'error') {
      $err = func_get_arg(1);
      if (count($this->__uncaught_exception_listeners__) === 0) {
        $err_str = $this->formatError($err);
        console_log($err_str);
        exit(1);
      }
      foreach ($this->__uncaught_exception_listeners__ as $listener) {
        $listener($err);
      }
    } else {
      call_user_func_array(array($this, 'parent::emit'), func_get_args());
    }
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

    $this->setTimeout(function ($main_module_filename) {
      require $main_module_filename;
    }, 0, $this->argv[1]);

    // catch exceptions
    $self = $this;
    set_exception_handler(function ($err) use ($self) {
      $self->emit('error', $err);
    });

    $this->event_loop->run();
  }


  public function setTimeout() {
    $args = func_get_args();
    return call_user_func_array(array($this->event_loop, 'setTimeout'), $args);
  }

  public function clearTimeout($handle) {
    $this->event_loop->clearTimeout($handle);
  }


  public function addStream(IStream $socket) {
    $this->event_loop->addStream($socket);
  }


  public function addCURLRequest($request) {
    $this->event_loop->addCURLRequest($request);
  }


  public function addChildProcess(ChildProcess $child_process) {
    $this->event_loop->addChildProcess($child_process);
  }


  public function formatError($err) {
    $stack = '';
    foreach ($err->getTrace() as $item) {
      $stack .= "  \033[0;37mat\033[0m ";

      if (!empty($item['class'])) {
        $stack .= str_replace('\\', "\033[0;37m.\033[0m", $item['class']);
        $stack .= " \033[0;37m->\033[0m ";
      } else {
        $stack .= "\033[0;37mfunction\033[0m ";
      }

      $fn = str_replace('{closure}', "\033[0;37manonymous\033[0;36m", $item['function']);
      $stack .= "\033[0;36m" . str_replace('\\', "\033[0m\033[0;37m.\033[0;36m", $fn);

      if (!empty($item['file'])) {
        $stack .= " \033[0;37m(\033[0m" . $item['file'];
        if (!empty($item['line'])) {
          $stack .= "\033[0;37m:\033[0;36m" . $item['line'] . "\033[0;37m";
        }
        $stack .= ")\033[0m";
      }

      $stack .= "\n";
    }

    return sprintf("\n\033[4;31m%s\033[0;31m: %s\033[0m\n%s", get_class($err), $err->getMessage(), $stack);
  }

}
