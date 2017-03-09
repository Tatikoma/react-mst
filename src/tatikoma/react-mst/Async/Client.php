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

    public function __construct(\React\EventLoop\LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;
        if(!isset($options['connectionString'])){
            throw new \InvalidArgumentException('Cannot start client without connectionString argument');
        }
        $this->connectionString = $options['connectionString'];
    }

    /**
     * @param $payload
     * @return \React\Promise\Promise
     * @throws \Exception
     */
    public function request($payload){
        $deferred = new \React\Promise\Deferred();
        $sequence = $this->getNextSequence();
        $this->queue[$sequence] = $deferred;
        $this->getStream()->write(pack('NN', strlen($payload) + 8, $sequence) . $payload);
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
            $socket = @stream_socket_client($this->connectionString, $errno, $errstr, 1, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);
            $this->stream = new \React\Stream\Stream($socket, $this->loop);
            $this->stream->on('end', function(){
                unset($this->stream);
                return $this->getStream();
            });
            $this->stream->on('close', function(){
                unset($this->stream);
                return $this->getStream();
            });
            $this->stream->on('error', function(){
                unset($this->stream);
                return $this->getStream();
            });
            $this->buffer = new \Tatikoma\React\MicroServiceTransport\Buffer($this->stream);
            $this->buffer->on('packet', function($payload){
                $header = unpack('Nlength/Nsequence', substr($payload, 0, 8));
                if(!isset($this->queue[$header['sequence']])){
                    throw new \LogicException('Received unknown sequence');
                }
                if($header['length'] === 8){
                    $this->queue[$header['sequence']]->reject();
                }
                else{
                    $this->queue[$header['sequence']]->resolve(substr($payload, 8));
                }
                unset($this->queue[$header['sequence']]);
            });
        }
        return $this->stream;
    }
}