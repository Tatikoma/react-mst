<?php
namespace Tatikoma\React\MicroServiceTransport;
interface ServiceInterface{
    /**
     * @param \React\EventLoop\LoopInterface $loop
     * @return mixed
     */
    public function init(\React\EventLoop\LoopInterface $loop);

    /**
     * @param string $request
     * @param array $header
     * @return \React\Promise\Promise
     */
    public function processRequest($request, array $header);
}