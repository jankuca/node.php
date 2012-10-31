**node.php**

An asynchronous PHP framework with an event loop (node.js-like)

## Installation

The preferred way is to use [**composer**](http://getcomposer.org).

    {
      "require": { "iankuca/node": "*" }
    }

Or just use this repository directly.

## Usage

    $ php -f main.php yourfile.php

## Examples

### Timeouts

```php
<?php
echo "A";
setTimeout(function () {
  echo "C";
}, 2000);
echo "B";
```

This prints `AB` and adds `C` after 2 seconds. Then it exits.

### HTTP Server

```php
<?php
$server = \Node\HTTP::createServer(function ($req, $res) {
  $res->writeHead(200, array(
    'content-type' => 'text/plain; charset=UTF-8'
  ));
  $res->write('Hello world!');
  $res->end();
});

$port = 8080;
$server->listen($port, 'localhost');
console_log('The HTTP server is listening on port %d.', $port);
```

This creates an HTTP server listening on the port 8080. It writes `Hello world!` to each response. It does not exit by itself.

### Directory Listing

```php
<?php
\Node\FS::readdir('/tmp', function ($err, $files) {
  if ($err) throw $err;
  print_r($files);
});
```

Spawns a child `ls -a /tmp` process and asynchronously calls the provided callback function when done. Then it exits.
