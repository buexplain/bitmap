<?php

declare(strict_types=1);

namespace BitMap;

use Spiral\Goridge\Exceptions\PrefixException;
use Spiral\Goridge\Exceptions\TransportException;
use Swoole\ConnectionPool;
use Swoole\Timer;
use Throwable;

class RPCProxy implements RPCInterface
{
    /**
     * @var ConnectionPool
     */
    protected $pool;

    protected $size = 0;

    /**
     * @var null|int
     */
    protected $timerId;

    public function __construct(int $size)
    {
        if ($size <= 0) {
            $size = 32;
        }
        $this->size = $size;
        $this->pool = new ConnectionPool(function () {
            return new RPC(RelayFactory::make());
        }, $this->size);
        $this->pool->fill();
        $this->initHeartbeat();
    }

    public function __destruct()
    {
        if ($this->timerId) {
            Timer::clear($this->timerId);
            $this->timerId = null;
        }
        if ($this->pool instanceof ConnectionPool) {
            $this->pool->close();
            $this->pool = null;
        }
    }

    protected function initHeartbeat()
    {
        $this->timerId = Timer::tick(20 * 1000, function ($id) {
            echo '定时器' . $id . PHP_EOL;
            if (is_null($this->pool)) {
                if ($this->timerId) {
                    Timer::clear($this->timerId);
                    $this->timerId = null;
                }
                return;
            }
            try {
                for ($i = 0; $i < $this->size; $i++) {
                    /**
                     * @var $rpc RPC
                     */
                    $rpc = $this->pool->get(0.1);
                    if ($rpc->call('Service.Ping', 'ping') == 'pong') {
                        $this->pool->put($rpc);
                    } else {
                        $this->pool->put(null);
                    }
                }
            } catch (Throwable $throwable) {
                //异常，视为连接破裂
                $this->pool->put(null);
            }
        });
    }

    /**
     * @throws ReconnectException
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
        } catch (TransportException | PrefixException | ReconnectException $exception) {
            //数据读写异常，视为连接破裂
            $this->pool->put(null);
            throw $exception;
        }
    }

    /**
     * @throws Throwable
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
        } catch (TransportException | PrefixException | ReconnectException $exception) {
            //数据读写异常，视为连接破裂
            $this->pool->put(null);
            throw $exception;
        }
    }
}
