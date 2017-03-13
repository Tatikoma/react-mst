<?php

namespace Tatikoma\React\MicroServiceTransport;
class Client{
    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;
    /**
     * @var \Tatikoma\React\MicroServiceTransport\Async\Client
     */
    protected $client;
    public function __construct($options)
    {
        $this->loop = \React\EventLoop\Factory::create();
        $this->client = new \Tatikoma\React\MicroServiceTransport\Async\Client($this->loop, $options);
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