<?php

declare(strict_types=1);

namespace BitMap;

use Spiral\Goridge\RPC as BaseRPC;

class RPC implements RPCInterface
{
    /**
     * @var Relay
     */
    protected $relay;

    /**
     * @var BaseRPC
     */
    private $rpc;

    public function __construct(Relay $relay)
    {
        $this->relay = $relay;
        $this->rpc = new BaseRPC($this->relay->connection);
    }

    public function call(string $method, $payload, int $flags = 0)
    {
        return $this->rpc->call($method, $payload, $flags);
    }

    public function getID(): array
    {
        $objectID = $this->rpc->call('Service.New', $this->relay->id);
        return ['connectionID'=>$this->relay->id, 'objectID'=>$objectID];
    }

    public function gc(array $id)
    {
        if ($this->relay->connection->isConnected()) {
            $this->rpc->call('Service.Destruct', $id);
        }
    }
}
