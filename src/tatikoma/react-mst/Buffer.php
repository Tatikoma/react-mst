<?php
namespace Tatikoma\React\MicroServiceTransport;
class Buffer{
    use \Evenement\EventEmitterTrait;

    protected $buffer = '';

    /**
     * @var \React\Socket\Connection|\React\Stream\Stream
     */
    public $connection;

    /**
     * Buffer constructor.
     * @param \React\Socket\ConnectionInterface|\React\Stream\Stream $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->connection->on('data', function($data){
            $this->buffer .= $data;
            $this->parseBuffer();
        });
    }

    /**
     * Close connection
     */
    public function close(){
        $this->connection->close();
    }

    /**
     * Parse data in buffer
     */
    public function parseBuffer(){
        if(strlen($this->buffer) < Common::HEADER_LENGTH) {
            return;
        }
        $packetSize = Common::readLength($this->buffer);
        while(strlen($this->buffer) >= $packetSize){

            $packet = substr($this->buffer, 0, $packetSize);
            $this->buffer = substr($this->buffer, $packetSize);

            $this->emit('packet', [$packet]);

            if(strlen($this->buffer) < Common::HEADER_LENGTH){
                break;
            }
            $packetSize = Common::readLength($this->buffer);
        }
    }
}