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
            try {
                //因为底层close方法没有判断是否已经关闭了的情况，所以重复关闭会导致错误
                //Call to a member function close() on null
                $this->pool->close();
                $this->pool = null;
            }catch (Throwable $throwable) {
            }
        }
    }

    protected function initHeartbeat()
    {
        Coroutine::create(function () {
            $runningHeartbeat = true;
            $runningSignal = true;
            $sleep = new Coroutine\Channel();
            Coroutine::create(function () use (&$runningHeartbeat, &$runningSignal, $sleep) {
                //这里支持自定义配置等待信号超时时间
                //因为swoole不支持同时唤醒两个不同超时时间的等待信号的协程
                //所以设置这个超时时间必须与其它等待信号的协程的超时时间一致
                //而且不要超过 max_wait_time 配置的时间
                //因为进程在收到信号的那一个刻会有一个等待信号的协程被唤醒，其它要被唤醒的协程只能等待下一个timeout之后
                $waitSignalTimeout = defined('BITMAP_WAIT_SIGNAL_TIMEOUT') ? BITMAP_WAIT_SIGNAL_TIMEOUT : 5;
                if(!is_int($waitSignalTimeout)) {
                    $waitSignalTimeout = 5;
                }
                while ($runningSignal) {
                    $ret = Coroutine::waitSignal(SIGTERM, $waitSignalTimeout);
                    if ($ret) {
                        $runningHeartbeat = false;
                        //收到结束信号
                        $sleep->push(true);
                        break;
                    }
                }
            });
            $fill = false;
            while ($runningHeartbeat) {
                //每隔多少秒进行一次心跳检查
                if ($sleep->pop(45)) {
                    break;
                }
                if ($this->pool === null) {
                    $runningSignal = false;
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
                            } catch (Throwable $throwable) {
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
