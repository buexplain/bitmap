<?php


namespace BitMap;

use Spiral\Goridge\Exceptions\ServiceException;
use ErrorException;

interface RPCInterface
{
    /**
     * @param string $method
     * @param $payload
     * @param int $flags
     * @return mixed
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function call(string $method, $payload, int $flags = 0);

    public function getID(): array;
}
