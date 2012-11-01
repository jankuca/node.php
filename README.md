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

### HTTP Request

```php
<?php
$options = array(
  'host' => 'ifconfig.me',
  'path' => '/ip'
);

\Node\HTTP::request($options, function ($res) {
  console_log('status: %d', $res->status);
  print_r($res->headers);

  $res->on('data', function ($chunk) {
    echo $chunk;
  });
});
```

This makes an HTTP `GET` request to `http://ifoconfig.me/ip` and asynchronously calls the provided callback function when a response object is ready. If waits for response body. Then it exits.

The standard output would be…

    status: 200
    Array
    (...)
    XX.XX.XX.XX


### Directory Listing

```php
<?php
\Node\FS::readdir('/tmp', function ($err, $files) {
  if ($err) throw $err;
  print_r($files);
});
```

Spawns a child `ls -a /tmp` process and asynchronously calls the provided callback function when done. Then it exits.

## Logging

Since the standard output of the process does not go to the browser, eventual exceptions and warnings are visible in the terminal window. The native exception stringifier is pretty horrible which is why *node.php* includes its own error formatter. Exceptions are now a lot nicer:

![Exception Example](https://s3.amazonaws.com/files.droplr.com/files_production/acc_33314/ghLx?AWSAccessKeyId=AKIAJSVQN3Z4K7MT5U2A&Expires=1351717311&Signature=KxYEe7n8VUDhqa5y%2BOauAhEfb2M%3D&response-content-disposition=inline%3B%20filename%2A%3DUTF-8%27%27Screenshot%2B2012-10-31%2Bat%2B20.59.15.png)

Another addition is the `console_log()` function which is basically `sprintf` that outputs to the standard output with an added end-of-line (`\n`) character.

```php
console_log('%d + %d = %s', 1, 2, 'awesome');
// stdout: 1 + 2 = awesome
