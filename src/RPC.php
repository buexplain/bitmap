<?php

declare(strict_types=1);

namespace BitMap;

use Spiral\Goridge\Exceptions\PrefixException;
use Spiral\Goridge\Exceptions\ServiceException;
use Spiral\Goridge\Exceptions\TransportException;
use Spiral\Goridge\RPC as BaseRPC;
use ErrorException;
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
     * @param string $method
     * @param $payload
     * @param int $flags
     * @return mixed|string
     * @throws ReconnectException
     * @throws ServiceException
     * @throws ErrorException
     */
    public function call(string $method, $payload, int $flags = 0)
    {
        try {
            return $this->rpc->call($method, $payload, $flags);
            //异常 ErrorException 被\socket_send函数抛出，因为高版本的php倾向于抛出异常，此处别被编辑器欺骗了
        } catch (ErrorException | TransportException | PrefixException $exception) {
            //服务端断掉了客户端的连接，但是客户端没感应，再次写入数据会抛出异常信息：socket_send(): unable to write to socket [32]: Broken pipe
            if (!$exception instanceof ErrorException || stripos($exception->getMessage(), 'pipe') == false) {
                throw $exception;
            }
            //连接挂了，尝试重连
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
     * @throws ReconnectException|ErrorException
     */
    public function getID(): array
    {
        $objectID = $this->call('Service.New', $this->relay->id);
        return ['connectionID' => $this->relay->id, 'objectID' => $objectID];
    }
}
