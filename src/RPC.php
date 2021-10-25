<?php

declare(strict_types=1);

namespace BitMap;

use Spiral\Goridge\Exceptions\PrefixException;
use Spiral\Goridge\Exceptions\TransportException;
use Spiral\Goridge\RPC as BaseRPC;
use Throwable;

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

    public function __destruct()
    {
        try {
            $this->relay->__destruct();
            $this->rpc = null;
        } catch (Throwable $throwable) {

        }
    }

    /**
     * @throws ReconnectException
     */
    public function call(string $method, $payload, int $flags = 0)
    {
        try {
            return $this->rpc->call($method, $payload, $flags);
        } catch (TransportException | PrefixException $exception) {
            $oldConnectionId = $this->relay->id;
            try {
                $this->relay->reconnect();
                $this->relay->askConnectionId();
            } catch (Throwable $throwable) {
                throw new ReconnectException($throwable->getMessage(), $throwable->getCode(), $throwable);
            }
            if ($oldConnectionId != $this->relay->id) {
                throw new ReconnectException('reconnect failed because the connection ID expired');
            }
            return $this->rpc->call($method, $payload, $flags);
        }
    }

    /**
     * @throws ReconnectException
     */
    public function getID(): array
    {
        $objectID = $this->call('Service.New', $this->relay->id);
        return ['connectionID' => $this->relay->id, 'objectID' => $objectID];
    }
}
