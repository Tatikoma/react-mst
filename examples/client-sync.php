<?php
require_once __DIR__ . '/../vendor/autoload.php';
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