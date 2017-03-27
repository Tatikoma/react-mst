<?php
require_once __DIR__ . '/../vendor/autoload.php';
$loop = \React\EventLoop\Factory::create();

$client = \Tatikoma\React\MicroServiceTransport\Factory::AsyncFailover($loop, [
    'connectionString' => '127.0.0.1:9009',
    'retry_count' => 3,
    'retry_interval' => 1,
]);

$client->request('Hello')->then(function($result){
    print $result . PHP_EOL;
})->otherwise(function(){
    print 'Request failed' . PHP_EOL;
})->done(function () use ($client) {
    $client->close();
});

$loop->run();