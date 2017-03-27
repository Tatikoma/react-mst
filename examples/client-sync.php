<?php
require_once __DIR__ . '/../vendor/autoload.php';

$client = \Tatikoma\React\MicroServiceTransport\Factory::Failover([
    'connectionString' => '127.0.0.1:9009',
    'retry_count' => 3,
    'retry_interval' => 1,
]);

try{
    $result = $client->request('Hello');
    print $result . PHP_EOL;
}
catch(Exception $e){
    print 'Request failed' . PHP_EOL;
}