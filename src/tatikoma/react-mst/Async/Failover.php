<?php
namespace Tatikoma\React\MicroServiceTransport\Async;
class Failover
{
    /**
     * @var \React\EventLoop\LoopInterface
     */
    public $loop;
    /**
     * @var Client
     */
    public $client;
    /**
     * @var int count to retry request
     */
    public $retry_count = 3;
    /**
     * @var int interval between request retry
     */
    public $retry_interval = 30;

    /**
     * Failover constructor.
     * @param \React\EventLoop\LoopInterface $loop
     * @param array $options
     * @throws \Exception
     */
    public function __construct(\React\EventLoop\LoopInterface $loop, array $options)
    {
        if (!isset($options['client'])) {
            throw new \InvalidArgumentException('Option client should be passed to constructor');
        }
        if (!($options['client'] instanceof Client)) {
            throw new \InvalidArgumentException('Option client should be instance of \Tatikoma\React\MicroServiceTransport\Async\Client');
        }
        if (isset($options['retry_count'])) {
            if (!is_int($options['retry_count'])) {
                throw new \InvalidArgumentException('Option retry_count should be integer');
            }
            $this->retry_count = $options['retry_count'];
        }
        if (isset($options['retry_interval'])) {
            if (!is_int($options['retry_interval'])) {
                throw new \InvalidArgumentException('Option retry_interval should be integer');
            }
            $this->retry_interval = $options['retry_interval'];
        }
        $this->loop = $loop;
        $this->client = $options['client'];
    }

    /**
     * @param string $payload
     * @param int $factor locking/sharding key
     * @return \React\Promise\Promise|\React\Promise\PromiseInterface
     * @throws \Exception
     */
    public function request($payload, $factor = 0)
    {
        $deferred = new \React\Promise\Deferred();
        $retryNo = 0;

        $onFullfilled = function ($result) use ($deferred) {
            $deferred->resolve($result);
        };
        $onRejected = function ($result) use ($deferred, &$retryNo, &$doRequest) {
            if (++$retryNo > $this->retry_count) {
                $deferred->reject($result);
            } else {
                $this->loop->addTimer($this->retry_interval, $doRequest);
            }
        };
        $doRequest = function () use ($payload, $factor, $onFullfilled, $onRejected) {
            $this->client->request($payload, $factor)->then($onFullfilled, $onRejected);
        };
        $doRequest();

        return $deferred->promise();
    }

    /**
     * Close client
     */
    public function close()
    {
        $this->client->close();
    }
}