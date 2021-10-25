<?php

declare(strict_types=1);

namespace BitMap;

use Swoole\Coroutine;
use Throwable;

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
        $this->inSwoole = class_exists('\Swoole\Coroutine') && Coroutine::getCid() > 0;
        if ($this->inSwoole) {
            $this->proxy = new RPCProxy(32);
        }
        $this->rpc = new RPC(RelayFactory::make());
    }

    public function __destruct()
    {
        try {
            if ($this->proxy) {
                $this->proxy->__destruct();
                $this->proxy = null;
            }
            if ($this->rpc) {
                $this->rpc->__destruct();
                $this->rpc = null;
            }
        } catch (Throwable $throwable) {

        }
    }

    public static function getInstance(): ClientFactory
    {
        if (static::$instance == null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    protected function createByProxy(): Client
    {
        return new Client($this->proxy);
    }

    protected function createByRPC(): Client
    {
        return new Client($this->rpc);
    }

    public function get(): Client
    {
        if ($this->inSwoole) {
            return $this->createByProxy();
        } else {
            return $this->createByRPC();
        }
    }

    public static function make(): Client
    {
        return static::getInstance()->get();
    }
}
