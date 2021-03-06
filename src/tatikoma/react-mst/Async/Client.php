<?php
namespace Tatikoma\React\MicroServiceTransport\Async;
class Client{
    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;
    /**
     * @var \React\Stream\Stream
     */
    protected $stream;
    /**
     * @var string connection string
     */
    protected $connectionString = '';
    /**
     * @var \Tatikoma\React\MicroServiceTransport\Buffer
     */
    protected $buffer;
    /**
     * @var int next sequence
     */
    protected $sequence = 0;
    /**
     * @var \React\Promise\Deferred[]
     */
    protected $queue = [];
    /**
     * @var int Number of maximum asynchronous requests
     */
    protected $asyncWindowSize = 0xFF;

    /**
     * Client constructor.
     * @param \React\EventLoop\LoopInterface $loop
     * @param array $options
     * @throws \Exception
     */
    public function __construct(\React\EventLoop\LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;
        if(!isset($options['connectionString'])){
            throw new \InvalidArgumentException('Cannot start client without connectionString argument');
        }
        if (isset($options['asyncWindowSize'])) {
            $this->asyncWindowSize = $options['asyncWindowSize'];
        }
        $this->connectionString = $options['connectionString'];
    }

    /**
     * @param string $payload
     * @param int $factor locking/sharding key
     * @return \React\Promise\Promise|\React\Promise\PromiseInterface
     * @throws \Exception
     */
    public function request($payload, $factor = 0){
        if ($this->asyncWindowSize > 0 && count($this->queue) >= $this->asyncWindowSize) {
            \Clue\React\Block\await(\React\Promise\any(array_map(function (\React\Promise\Deferred $item) {
                return $item->promise();
            }, $this->queue)), $this->loop);
        }
        $deferred = new \React\Promise\Deferred();
        $sequence = $this->getNextSequence();
        $this->queue[$sequence] = $deferred;
        $this->getStream()->write(
            \Tatikoma\React\MicroServiceTransport\Common::writeHeader($payload, [
                'sequence' => $sequence,
                'factor' => $factor,
            ])
        );
        return $deferred->promise();
    }

    /**
     * @return int
     */
    protected function getNextSequence(){
        if($this->sequence >= 0xFFFFFFFF){
            $this->sequence = 0;
        }
        return $this->sequence++;
    }

    /**
     * @return \React\Stream\Stream
     * @throws \Exception
     */
    protected function getStream(){
        if(null === $this->stream || !$this->stream->isWritable()){
            if(null !== $this->buffer){
                $this->buffer->close();
            }
            $socket = stream_socket_client($this->connectionString, $errno, $errstr, 1, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

            $streamErrorHandler = function () {
                $this->stream->removeAllListeners();
                $this->stream->close();

                $this->stream = null;
                foreach ($this->queue as $sequence => $deferred) {
                    $deferred->reject(new \RuntimeException('Connection closed during request..'));
                }
            };

            $this->stream = new \React\Stream\Stream($socket, $this->loop);
            $this->stream->on('end', $streamErrorHandler);
            $this->stream->on('close', $streamErrorHandler);
            $this->stream->on('error', $streamErrorHandler);
            $this->buffer = new \Tatikoma\React\MicroServiceTransport\Buffer($this->stream);
            $this->buffer->on('packet', function($payload){
                $header = \Tatikoma\React\MicroServiceTransport\Common::readHeader($payload);
                if(!isset($this->queue[$header['sequence']])){
                    throw new \LogicException('Received unknown sequence');
                }
                if ($header['data'] === '') {
                    $this->queue[$header['sequence']]->reject();
                } else {
                    $this->queue[$header['sequence']]->resolve($header['data']);
                }
                unset($this->queue[$header['sequence']]);
            });
        }
        return $this->stream;
    }

    /**
     * Close client
     */
    public function close()
    {
        if ($this->stream instanceof \React\Stream\Stream) {
            $this->stream->close();
        }
    }
}