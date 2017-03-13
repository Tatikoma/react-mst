<?php
require_once __DIR__ . '/../vendor/autoload.php';
$loop = \React\EventLoop\Factory::create();

$client = new \Tatikoma\React\MicroServiceTransport\Async\Client($loop, [
    'connectionString' => '127.0.0.1:9009',
]);

$client->request('Hello')->then(function($result){
    print $result . PHP_EOL;
})->otherwise(function(){
    print 'Request failed' . PHP_EOL;
})->then(function () use ($client) {
    $client->close();
});

$loop->run();