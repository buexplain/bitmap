<?php

declare(strict_types=1);

namespace BitMap;

use ErrorException;
use Error;
use Spiral\Goridge\Exceptions\ServiceException;
use Swoole\ConnectionPool;
use Swoole\Coroutine;
use Throwable;

class RPCProxy implements RPCInterface
{
    /**
     * @var ConnectionPool
     */
    protected $pool;

    protected $size = 0;

    public function __construct(int $size)
    {
        if ($size <= 0) {
            $size = 32;
        }
        $this->size = $size;
        $this->pool = new ConnectionPool(function () {
            return new RPC(RelayFactory::make());
        }, $this->size);
        $this->initHeartbeat();
    }

    public function __destruct()
    {
        if ($this->pool instanceof ConnectionPool) {
            $this->pool->close();
            $this->pool = null;
        }
    }

    protected function initHeartbeat()
    {
        Coroutine::create(function () {
            $ch = new Coroutine\Channel();
            $fill = false;
            while (true) {
                if ($this->pool === null) {
                    $ch->close();
                    break;
                }
                $ch->pop(30);
                if ($this->pool === null) {
                    $ch->close();
                    break;
                }
                if (!$fill) {
                    //填满整个连接池，有可能发生异常，导致进程挂了
                    try {
                        $this->pool->fill();
                    } catch (Throwable $throwable) {
                    }
                    $fill = true;
                }
                for ($i = 0; $i < $this->size; $i++) {
                    try {
                        /**
                         * @var $rpc RPC
                         */
                        $rpc = $this->pool->get(0.1);
                        if (!$rpc instanceof RPCInterface) {
                            continue;
                        }
                        if ($rpc->call('Service.Ping', 'ping') == 'pong') {
                            $this->pool->put($rpc);
                        } else {
                            try {
                                $this->pool->put(null);
                            }catch (Throwable $throwable) {
                            }
                        }
                    } catch (Throwable $throwable) {
                        //异常，视为连接破裂
                        try {
                            //回收空连接的时，连接池会构建新的连接，此时也可能发生异常，需要捕获，否则会导致进程挂掉
                            $this->pool->put(null);
                        } catch (Throwable $throwable) {
                        }
                    }
                }
            }
        });
    }

    /**
     * @param string $method
     * @param $payload
     * @param int $flags
     * @return mixed|string|null
     * @throws ServiceException|ReconnectException|ErrorException|Error|Throwable
     */
    public function call(string $method, $payload, int $flags = 0)
    {
        if (is_null($this->pool)) {
            return null;
        }
        /**
         * @var $rpc RPC
         */
        $rpc = $this->pool->get();
        try {
            $result = $rpc->call($method, $payload, $flags);
            $this->pool->put($rpc);
            return $result;
        } catch (Throwable $throwable) {
            if ($throwable instanceof ReconnectException) {
                $this->pool->put(null);
            } else {
                $this->pool->put($rpc);
            }
            throw $throwable;
        }
    }

    /**
     * @throws ServiceException|ReconnectException|ErrorException|Error|Throwable
     */
    public function getID(): array
    {
        /**
         * @var $rpc RPC
         */
        $rpc = $this->pool->get();
        try {
            $id = $rpc->getID();
            $this->pool->put($rpc);
            return $id;
        } catch (Throwable $throwable) {
            if ($throwable instanceof ReconnectException) {
                $this->pool->put(null);
            } else {
                $this->pool->put($rpc);
            }
            throw $throwable;
        }
    }
}
