Performant asynchronous pure PHP Micro Service Transport using plain TCP layer based on ReactPHP.

Library not enough much functional and not recommended for production use. For production use need to provide some much functional like: logging, statistics, debug, unit tests, timeouts, better error handling.

This library provides clients and server:

Server - listen tcp port, receive connections, fork workers and pass requests to workers. If worker closed connection, then restarts worker.

Client (async promises and sync) - sends request to listening server and returns result.

Installation:
```
composer require tatikoma/react-mst:dev-master
```

Example usage (server):
```php
require_once __DIR__ . '/vendor/autoload.php';
class TestService implements \Tatikoma\React\MicroServiceTransport\ServiceInterface {

    /**
     * @param string $request request data
     * @return \React\Promise\Promise
     */
    public function processRequest($request)
    {
        return \React\Promise\resolve('Result: ' . strlen($request));
    }

    /**
     * Initialize service
     */
    public function init()
    {
        // do some things, like connection to database
    }
}

$loop = React\EventLoop\Factory::create();

$server = new \Tatikoma\React\MicroServiceTransport\Server($loop, [
    'listen' => '127.0.0.1:9009', // listen host:port
    'no_workers' => 4, // number of workers
    'service' => new TestService(), // service instance
]);
$server->run();
```

Example usage (sync client):
```php
require_once __DIR__ . '/vendor/autoload.php';
$client = new \Tatikoma\React\MicroServiceTransport\Client([
    'connectionString' => '127.0.0.1:9009',
]);

try{
    $result = $client->request('Hello');
    print $result . PHP_EOL;
}
catch(Exception $e){
    print 'Request failed' . PHP_EOL;
}
```

Example usage (async client):
```php
require_once __DIR__ . '/vendor/autoload.php';
$loop = \React\EventLoop\Factory::create();

$client = new \Tatikoma\React\MicroServiceTransport\Async\Client($loop, [
    'connectionString' => '127.0.0.1:9009',
]);

$client->request('Hello')->then(function($result){
    print $result . PHP_EOL;
})->otherwise(function(){
    print 'Request failed' . PHP_EOL;
});

$loop->run();
```