<?php
require_once __DIR__ . '/../vendor/autoload.php';
class TestService implements \Tatikoma\React\MicroServiceTransport\ServiceInterface {

    /**
     * @param string $request request data
     * @param array $header
     * @return \React\Promise\Promise
     */
    public function processRequest($request, array $header)
    {
        return \React\Promise\resolve('Result: ' . strlen($request));
    }

    /**
     * Initialize service
     */
    public function init()
    {
        // do some initial things, like connection to database
    }
}

$loop = React\EventLoop\Factory::create();

$server = new \Tatikoma\React\MicroServiceTransport\Server($loop, [
    'listen' => '127.0.0.1:9009', // listen host:port
    'no_workers' => 4, // number of workers
    'service' => new TestService(), // service instance
]);
$server->run();