<?php

namespace Node;

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

require_once __DIR__ . '/src/Process.php';
require_once __DIR__ . '/functions.php';

require_once __DIR__ . '/src/HTTP.php';
require_once __DIR__ . '/src/FS.php';


$process = new Process($_SERVER);

unset($_SERVER);

$process->main();

