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

    /**
     * ClientFactory constructor.
     * @throws \Spiral\Goridge\Exceptions\RelayException
     */
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
        //判断是否为协程环境
        if (class_exists('\Swoole\Coroutine')) {
            $cid = Coroutine::getCid();
        } else {
            $cid = 0;
        }
        if (static::$instance === null) {
            //拿到初始化的机会，先给个false，避免其它的协程也走到这一步，这一步是纯cpu操作，不会引起协程调度
            static::$instance = false;
            //初始化对象，并返回
            static::$instance = new static();
            return static::$instance;
        }
        //没有机会初始化的对象的协程，死循环等待其它协程初始化成功
        $wait = 0;
        while ($wait < 10) {
            if ($cid > 0) {
                //协程环境，进行休眠，让出cpu时间给初始化对象的协程使用
                Coroutine::sleep(0.1);
            }else{
                usleep(100);
            }
            if (static::$instance instanceof ClientFactory) {
                return static::$instance;
            }
            $wait++;
        }
        //等待次数太多，直接异常
        throw new ReconnectException('Failed to instantiate ClientFactory object');
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

    /**
     * @return \BitMap\Client
     * @throws \Spiral\Goridge\Exceptions\RelayException
     */
    public static function make(): Client
    {
        return static::getInstance()->get();
    }
}
