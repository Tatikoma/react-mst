<?php

require_once __DIR__ . '/vendor/autoload.php';


class TestService implements \Tatikoma\React\MicroServiceTransport\ServiceInterface {

    /**
     * @param $request
     * @return \React\Promise\Promise
     */
    public function processRequest($request)
    {
        return \React\Promise\resolve('??');
    }

    /**
     * @return mixed
     */
    public function init()
    {
        // TODO: Implement init() method.
    }
}

$loop = React\EventLoop\Factory::create();

$server = new \Tatikoma\React\MicroServiceTransport\Server($loop, [
    'listen' => '127.0.0.1:9009',
    'no_workers' => 4,
    'service' => new TestService(),
]);
$server->run();