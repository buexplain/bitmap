<?php

declare(strict_types=1);

namespace BitMap;

use Swoole\ConnectionPool;
use Swoole\Coroutine;

class ClientFactory
{
    /**
     * @var self
     */
    protected static $instance;

    /**
     * @var RPCProxy
     */
    protected $proxy;

    /**
     * @var RPC
     */
    protected $rpc;

    protected $inSwoole = false;

    protected function __construct()
    {
        $this->inSwoole = class_exists('\Swoole\Coroutine');
        if ($this->inSwoole) {
            $this->proxy = new RPCProxy(new ConnectionPool(function () {
                return new RPC(RelayFactory::make());
            }, 32));
        }
        $this->rpc = new RPC(RelayFactory::make());
    }

    protected function createByProxy(): Client
    {
        return new Client($this->proxy);
    }

    protected function createByRPC(): Client
    {
        return new Client($this->rpc);
    }

    public static function make(): Client
    {
        if (static::$instance == null) {
            static::$instance = new static();
        }
        if (static::$instance->inSwoole && Coroutine::getCid() > 0) {
            return static::$instance->createByProxy();
        } else {
            return static::$instance->createByRPC();
        }
    }
}
