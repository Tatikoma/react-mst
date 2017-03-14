<?php
require_once __DIR__ . '/../vendor/autoload.php';
class TestService implements \Tatikoma\React\MicroServiceTransport\ServiceInterface {
    protected $loop;

    /**
     * Initialize service
     * @param \React\EventLoop\LoopInterface $loop
     * @return mixed|void
     */
    public function init(\React\EventLoop\LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * @param string $request request data
     * @param array $header
     * @return \React\Promise\Promise
     */
    public function processRequest($request, array $header)
    {
        return \React\Promise\resolve('Result: ' . strlen($request));
    }
}

$loop = React\EventLoop\Factory::create();

$server = new \Tatikoma\React\MicroServiceTransport\Server($loop, [
    'listen' => '127.0.0.1:9009', // listen host:port
    'no_workers' => 4, // number of workers
    'service' => new TestService(), // service instance
]);
$server->run();