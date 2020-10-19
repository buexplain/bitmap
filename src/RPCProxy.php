<?php

declare(strict_types=1);

namespace BitMap;

use Swoole\ConnectionPool;

class RPCProxy implements RPCInterface
{
    /**
     * @var ConnectionPool
     */
    protected $pool;

    public function __construct(ConnectionPool $pool)
    {
        $this->pool = $pool;
    }


    public function call(string $method, $payload, int $flags = 0)
    {
        $rpc = $this->pool->get();
        $result = $rpc->call($method, $payload, $flags);
        $this->pool->put($rpc);
        return $result;
    }

    public function getID(): array
    {
        $retry = 1;
        loop:
        try {
            /**
             * @var $rpc RPC
             */
            $rpc = $this->pool->get();
            $id = $rpc->getID();
            $this->pool->put($rpc);
            return $id;
        } catch (\Throwable $throwable) {
            $this->pool->put(null);
            if ($retry ==0) {
                throw $throwable;
            }
            $retry--;
            goto loop;
        }
    }

    public function gc(array $id)
    {
        $rpc = $this->pool->get();
        $rpc->gc($id);
        $this->pool->put($rpc);
    }
}
