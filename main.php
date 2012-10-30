<?php

namespace Node;

require __DIR__ . '/src/Process.php';
require __DIR__ . '/functions.php';


$process = new Process();
$process->main($_SERVER);


unset($_SERVER);
