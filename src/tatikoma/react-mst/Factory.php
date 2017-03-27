<?php
namespace Tatikoma\React\MicroServiceTransport;
/**
 * Class Factory
 * @package Tatikoma\React\MicroServiceTransport
 */
class Factory
{
    private function __construct()
    {
    }

    /**
     * Blocking failover
     * @param array $options
     * @return Blocking
     * @throws \Exception
     */
    static public function Failover(array $options = [])
    {
        $loop = \React\EventLoop\Factory::create();

        $client = new \Tatikoma\React\MicroServiceTransport\Async\Client($loop, $options);
        $failover = new \Tatikoma\React\MicroServiceTransport\Async\Failover($loop, array_merge([
            'client' => $client,
        ], $options));

        return new Blocking($loop, $failover);
    }

    /**
     * Blocking client
     * @param array $options
     * @return Blocking
     * @throws \Exception
     */
    static public function Client(array $options = [])
    {
        $loop = \React\EventLoop\Factory::create();
        $client = new \Tatikoma\React\MicroServiceTransport\Async\Client($loop, $options);

        return new Blocking($loop, $client);
    }

    /**
     * Async failover
     * @param \React\EventLoop\LoopInterface $loop
     * @param array $options
     * @return Async\Failover
     * @throws \Exception
     */
    static public function AsyncFailover(\React\EventLoop\LoopInterface $loop, array $options = [])
    {
        $client = new \Tatikoma\React\MicroServiceTransport\Async\Client($loop, $options);

        return new \Tatikoma\React\MicroServiceTransport\Async\Failover($loop, array_merge([
            'client' => $client,
        ], $options));
    }

    /**
     * Async client
     * @param \React\EventLoop\LoopInterface $loop
     * @param array $options
     * @return Async\Client
     * @throws \Exception
     */
    static public function AsyncClient(\React\EventLoop\LoopInterface $loop, array $options = [])
    {
        return new \Tatikoma\React\MicroServiceTransport\Async\Client($loop, $options);
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
}