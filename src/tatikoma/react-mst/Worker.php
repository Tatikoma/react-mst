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
    public function __construct(\React\EventLoop\LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * Run worker
     */
    public function run(){
        $this->service->init();

        $this->buffer = new Buffer($this->connection);
        $this->buffer->on('packet', function($packet){
            $header = unpack('Nlength/Nsequence/Nconnection', substr($packet, 0, 12));
            $payload = substr($packet, 12);
            $this->service->processRequest($payload)->then(function($payload) use($header){
                $this->connection->write($pck  =
                    pack('NNN', strlen($payload) + 12, $header['sequence'], $header['connection'])
                    . $payload
                );
            })->otherwise(function() use($header){
                $this->connection->write(pack('NNN', 12, $header['sequence'], $header['connection']));
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