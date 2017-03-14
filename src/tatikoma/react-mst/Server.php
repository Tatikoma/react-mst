<?php
namespace Tatikoma\React\MicroServiceTransport;

class Server {
    use \Evenement\EventEmitterTrait;
    /**
     * @var string listen host and port
     */
    public $listen = '127.0.0.1:9009';
    /**
     * @var int number of worker childs
     */
    public $no_workers = 1;
    /**
     * @var ServiceInterface
     */
    public $service;
    /**
     * @var \React\EventLoop\LoopInterface
     */
    public $loop;
    /**
     * @var \React\Socket\Server
     */
    public $socket;
    /**
     * @var Worker[]
     */
    public $workers = [];
    /**
     * @var int[] list of child process pids
     */
    public $childs = [];
    /**
     * @var int sequential connection index
     */
    public $lastConnectionId = 0;
    /**
     * @var Buffer[]
     */
    public $connections = [];
    /**
     * @var int[][]
     */
    public $queue = [];
    /**
     * @var int
     */
    public $lastActiveWorker = 0;

    /**
     * Server constructor.
     * @param \React\EventLoop\LoopInterface $loop
     * @param array $options
     * @throws \Exception
     */
    public function __construct(\React\EventLoop\LoopInterface $loop, array $options = [])
    {
        if(isset($options['listen'])){
            $this->listen = $options['listen'];
        }
        if(isset($options['no_workers'])){
            $this->no_workers = $options['no_workers'];
        }
        if(!isset($options['service'])){
            throw new \InvalidArgumentException('Cannot run server without service');
        }
        $this->service = $options['service'];

        $this->loop = $loop;
    }

    /**
     * Run server
     * @throws \Exception
     */
    public function run(){
        $this->socket = new \React\Socket\Server($this->listen, $this->loop);

        $this->socket->on('error', function(){
            throw new \RuntimeException('Got error on master socket');
        });
        $this->socket->on('connection', function(\React\Socket\ConnectionInterface $connection){
            $connectionId = $this->lastConnectionId++;
            $this->connections[$connectionId] = new Buffer($connection);

            $this->connections[$connectionId]->on('packet', function($packet) use($connectionId){
                $header = Common::readHeader($packet);
                $header['connection'] = $connectionId;

                if($header['factor'] === 0) {
                    // no factor set, do round-robin
                    do {
                        $workerId = $this->lastActiveWorker++;
                        if ($workerId >= count($this->workers)) {
                            $this->lastActiveWorker = 0;
                        }
                    } while (!isset($this->workers[$workerId]));
                }
                else{
                    // use factor to select worker
                    $workerId = $header['factor'] % $this->no_workers;
                }

                $packetId = Common::getPacketId($header['sequence'], $connectionId);

                $this->queue[$workerId][$packetId] = 1;
                $this->workers[$workerId]->connection->write(
                    Common::writeHeader($header['data'], $header)
                );
            });

            $connection->on('end', function() use($connectionId){
                if(isset($this->connections[$connectionId])) {
                    $this->connections[$connectionId]->close();
                    unset($this->connections[$connectionId]);
                }
            });
            $connection->on('error', function() use($connectionId){
                if(isset($this->connections[$connectionId])) {
                    $this->connections[$connectionId]->close();
                    unset($this->connections[$connectionId]);
                }
            });
            $connection->on('close', function() use($connectionId){
                if(isset($this->connections[$connectionId])) {
                    $this->connections[$connectionId]->close();
                    unset($this->connections[$connectionId]);
                }
            });
        });

        /** @noinspection ForeachInvariantsInspection */
        for($i = 0; $i < $this->no_workers; $i++){
            $this->addWorker($i);
            $this->queue[$i] = [];
        }

        $this->loop->addPeriodicTimer(0.5, function(){
            // prevent zombie..
            foreach ($this->childs as $k => $child) {
                $result = pcntl_waitpid($child, $status, WNOHANG);
                if ($result === -1 || $result > 0) {
                    // process exited
                    unset($this->childs[$k]);
                }
            }
        });

        $this->loop->run();
    }

    /**
     * Add worker process
     * @param int $workerId worker id
     * @throws \Exception
     */
    protected function addWorker($workerId){
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $pid = pcntl_fork();
        if($pid === -1){
            throw new \RuntimeException('Cannot fork');
        }
        $worker = new Worker($this->loop, [
            'id' => $workerId,
        ]);
        //$worker->id = $workerId;
        if($pid > 0){
            // parent
            fclose($pair[0]);
            $this->childs[] = $pid;
            $worker->pid = $pid;
            $worker->connection = new \React\Stream\Stream($pair[1], $this->loop);
            $this->workers[$workerId] = $worker;

            $buffer = new Buffer($worker->connection);
            $buffer->on('packet', function ($data) use ($workerId) {
                $this->processWorkerData($workerId, $data);
            });
            $worker->connection->on('end', function() use($workerId){
                $this->processWorkerDeath($workerId);
            });
            $worker->connection->on('close', function() use($workerId){
                $this->processWorkerDeath($workerId);
            });
            $worker->connection->on('error', function() use($workerId){
                $this->processWorkerDeath($workerId);
            });
        }
        else{
            // child
            fclose($pair[1]);

            // remove master socket events
            $reflection = new \ReflectionClass($this->socket);
            $property = $reflection->getProperty('master');
            $property->setAccessible(true);
            $this->loop->removeStream($property->getValue($this->socket));
            $this->socket->removeAllListeners();

            foreach($this->connections as $connection){
                $connection->removeAllListeners();
                $this->loop->removeStream($connection->connection->stream);
            }

            foreach($this->workers as $workerOne){
                $workerOne->connection->removeAllListeners();
                $this->loop->removeStream($workerOne->connection->stream);
            }

            $worker->pid = getmypid();
            $worker->service = $this->service;
            $worker->connection = new \React\Stream\Stream($pair[0], $this->loop);
            $worker->run();
            return;
        }
    }

    /**
     * Process data from worker
     * @param int $workerId
     * @param string $packet
     */
    protected function processWorkerData($workerId, $packet){
        $header = Common::readHeader($packet);
        if(isset($this->connections[$header['connection']])){
            $packetId = Common::getPacketId($header['sequence'], $header['connection']);
            if(isset($this->queue[$workerId][$packetId])){
                unset($this->queue[$workerId][$packetId]);
            }
            $this->connections[$header['connection']]->connection->write(
                Common::writeHeader($header['data'], $header)
            );
        }
    }

    /**
     * Process dead worker
     * @param int $workerId worker id
     * @throws \Exception
     */
    protected function processWorkerDeath($workerId){
        $this->workers[$workerId]->connection->removeAllListeners();
        $this->workers[$workerId]->connection->close();
        posix_kill($this->workers[$workerId]->pid, SIGTERM);
        unset($this->workers[$workerId]);
        foreach($this->queue[$workerId] as $packetId => $null){
            $header = Common::parsePacketId($packetId);
            if(isset($this->connections[$header['connection']])){
                $this->connections[$header['connection']]->connection->write(
                    Common::writeHeader('', $header)
                );
            }
        }
        $this->queue[$workerId] = [];
        $this->addWorker($workerId);
    }
}