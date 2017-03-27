<?php

namespace Tatikoma\React\MicroServiceTransport;
class Blocking
{
    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;
    /**
     * @var \Tatikoma\React\MicroServiceTransport\Async\Client|\Tatikoma\React\MicroServiceTransport\Async\Failover
     */
    protected $client;

    public function __construct(\React\EventLoop\LoopInterface $loop, $client)
    {
        $this->loop = $loop;
        $this->client = $client;
    }

    /**
     * @param string $payload
     * @param int $factor locking/sharding key
     * @return string
     * @throws \Exception
     */
    public function request($payload, $factor = 0){
        return \Clue\React\Block\await($this->client->request($payload, $factor), $this->loop);
    }
}