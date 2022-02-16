<?php

declare(strict_types=1);

namespace BitMap;

use Spiral\Goridge\Exceptions\ServiceException;
use ErrorException;
use Error;
use Throwable;

interface RPCInterface
{
    /**
     * @param string $method
     * @param $payload
     * @param int $flags
     * @return mixed
     * @throws ServiceException|ReconnectException|ErrorException|Error|Throwable
     */
    public function call(string $method, $payload, int $flags = 0);

    public function getID(): array;
}
