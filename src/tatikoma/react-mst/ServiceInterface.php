<?php
namespace Tatikoma\React\MicroServiceTransport;
interface ServiceInterface{
    /**
     * @param $request
     * @return \React\Promise\Promise
     */
    public function processRequest($request);

    /**
     * @return mixed
     */
    public function init();
}