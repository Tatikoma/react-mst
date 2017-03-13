<?php
namespace Tatikoma\React\MicroServiceTransport;
class Worker{
    /**
     * @var int sequential worker id
     */
    public $id;

    /**
     * @var int worker process id
     */
    public $pid;
    /**
     * @var \React\Stream\Stream Worker connection
     */
    public $connection;
    /**
     * @var \React\EventLoop\LoopInterface
     */
    public $loop;
    /**
     * @var Buffer
     */
    public $buffer;
    /**
     * @var ServiceInterface
     */
    public $service;

    /**
     * Worker constructor.
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function __construct(\React\EventLoop\LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;
        if(isset($options['id'])){
            $this->id = $options['id'];
        }
    }

    /**
     * Run worker
     */
    public function run(){
        $this->service->init();

        $this->buffer = new Buffer($this->connection);
        $this->buffer->on('packet', function($packet){
            $header = Common::readHeader($packet);

            $this->service->processRequest($header['data'], $header)->then(function($payload) use($header){
                $this->connection->write(
                    Common::writeHeader($payload, $header)
                );
            })->otherwise(function() use($header){
                $this->connection->write(
                    Common::writeHeader('', $header)
                );
            });
        });

        $this->connection->on('end', function(){
            print("Got stream EOF\n");
            exit;
        });
        $this->connection->on('error', function(){
            print("Got stream error\n");
            exit;
        });
        $this->connection->on('close', function(){
            print("Got stream close\n");
            exit;
        });

        $this->loop->run();
    }
}