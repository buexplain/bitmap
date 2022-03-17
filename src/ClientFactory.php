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

    /**
     * @return \BitMap\ClientFactory
     * @throws \Throwable
     */
    public static function getInstance(): ClientFactory
    {
        if (static::$instance === null) {
            //拿到初始化的机会，先给个false，避免其它的协程也走到这一步，这一步是纯cpu操作，不会引起协程调度
            static::$instance = false;
            try {
                //初始化对象，这一步可能引起cpu调度，因为可能有连接bitmap server的动作
                static::$instance = new static();
                return static::$instance;
            } catch (Throwable $throwable) {
                //初始化失败，还原为null，以便下一个协程有机会初始化
                static::$instance = null;
                throw $throwable;
            }
        }
        //无论什么环境，直接判断是否已经初始化过
        if (static::$instance instanceof ClientFactory) {
            return static::$instance;
        }
        //获取协程id
        if (class_exists('\Swoole\Coroutine')) {
            $cid = Coroutine::getCid();
        } else {
            $cid = 0;
        }
        //非协程环境
        if ($cid <= 0) {
            //再次初始化
            if (!static::$instance instanceof ClientFactory) {
                static::$instance = new static();
            }
            return static::$instance;
        }
        //协程环境
        $wait = 0;
        while ($wait < 1) {
            //通过休眠让出cpu时间，给初始化对象的协程使用
            Coroutine::sleep(0.1);
            //判断其它协程是否初始化失败
            if (static::$instance === null) {
                throw new ReconnectException('Other coroutine failed to instantiate ClientFactory object');
            }
            //判断其它协程是否初始化成功
            if (static::$instance instanceof ClientFactory) {
                return static::$instance;
            }
            $wait++;
        }
        //等待次数太多，直接异常，导致这个原因是因为得到初始化机会的那个协程，没有把握住机会，初始化失败了
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

    /**
     * @return \BitMap\Client
     */
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
     * @throws \Throwable
     */
    public static function make(): Client
    {
        return static::getInstance()->get();
    }
}
