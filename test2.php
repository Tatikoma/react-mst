<?php

require_once __DIR__ . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$client = new \Tatikoma\React\MicroServiceTransport\Async\Client($loop, [
    'connectionString' => '127.0.0.1:9009',
]);

$response = 0;

$loop->addTimer(1, function() use(&$response){
    var_dump($response);
});

for($i = 0; $i < 100000; $i++){
    $client->request(str_repeat('??', 0xFFFF))->then(function() use(&$response){
        $response++;
    });
    $loop->tick();
}

$loop->run();

/*$client = new \Tatikoma\React\MicroServiceTransport\Client([
    'connectionString' => '127.0.0.1:9009',
]);

$s = microtime(true);

for($i = 0; $i < 10000; $i ++) {
    $result = $client->request('{}');
    var_dump($result);
}

$e = microtime(true);

var_dump($e - $s . "s\n");*/