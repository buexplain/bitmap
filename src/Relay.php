<?php

declare(strict_types=1);

namespace BitMap;

use Spiral\Goridge\SocketRelay;

class Relay
{
    /**
     * 连接对象
     * @var StreamSocketRelay|SocketRelay
     */
    public $connection;

    /**
     * 连接id
     * @var int
     */
    public $id = 0;
}
