<?php

declare(strict_types=1);

namespace BitMap;

use Spiral\Goridge\Exceptions\RelayException;
use Spiral\Goridge\Exceptions\ServiceException;
use Error;
use ErrorException;
use Throwable;
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

    public function __destruct()
    {
        try {
            $this->relay->__destruct();
            $this->rpc = null;
        } catch (Throwable $throwable) {

        }
    }

    /**
     * @param string $method
     * @param $payload
     * @param int $flags
     * @return mixed|string
     * @throws ServiceException|ReconnectException|ErrorException|Error|Throwable
     */
    public function call(string $method, $payload, int $flags = 0)
    {
        try {
            return $this->rpc->call($method, $payload, $flags);
        } catch (Throwable $throwable) {
            if (
                //服务端断掉了客户端的连接，但是客户端没感应，再次写入数据会抛出异常信息：socket_send(): unable to write to socket [32]: Broken pipe
                ($throwable instanceof ErrorException && stripos($throwable->getMessage(), 'pipe') !== false) ||
                //服务端挂了会返回Return value of swoole_socket_write() must be of the type int, bool returned
                ($throwable instanceof Error && stripos($throwable->getMessage(), 'socket_write') !== false) ||
                $throwable instanceof RelayException
            ) {
                //连接挂了，尝试重连
                $oldConnectionId = $this->relay->id;
                try {
                    $this->relay->reconnect();
                    $this->relay->askConnectionId();
                } catch (Throwable $throwable) {
                    //此处将所有的异常都转为重连异常
                    throw new ReconnectException($throwable->getMessage(), $throwable->getCode());
                }
                if ($oldConnectionId != $this->relay->id) {
                    throw new ReconnectException('reconnect failed because the connection ID expired');
                }
                return $this->rpc->call($method, $payload, $flags);
            }
            throw $throwable;
        }
    }

    /**
     * @throws ServiceException|ReconnectException|ErrorException|Error|Throwable
     */
    public function getID(): array
    {
        $objectID = $this->call('Service.New', $this->relay->id);
        return ['connectionID' => $this->relay->id, 'objectID' => $objectID];
    }
}
