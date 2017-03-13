<?php
namespace Tatikoma\React\MicroServiceTransport;
interface ServiceInterface{
    /**
     * @param string $request
     * @param array $header
     * @return \React\Promise\Promise
     */
    public function processRequest($request, array $header);

    /**
     * @return mixed
     */
    public function init();
}