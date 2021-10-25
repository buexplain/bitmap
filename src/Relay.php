<?php

declare(strict_types=1);

namespace BitMap;

use Spiral\Goridge\SocketRelay;
use Spiral\Goridge\Exceptions\RelayException;
use Throwable;

class Relay
{
    /**
     * 连接对象
     * @var StreamSocketRelay|SocketRelay
     */
    public $connection;

    /**
     * 连接id
     * @var int
     */
    public $id = 0;

    public function __destruct()
    {
        try {
            if ($this->connection) {
                $this->connection->close();
                $this->connection = null;
            }
        } catch (Throwable $throwable) {

        }
    }

    /**
     * 询问连接id
     * 如果从未有过连接id，服务端会下发新的连接id
     * 如果已有连接id，服务端会判断客户端发送的连接id是否过期
     * 如果过期，会下发新的连接id，未过期则会将客户端的旧连接id返回，表示继续使用旧的连接id
     */
    public function askConnectionId()
    {
        //发送旧的连接id
        $this->connection->send((string)$this->id, 0);
        //丢弃头部信息
        $this->connection->receiveSync();
        //读取本次连接的id
        $id = intval($this->connection->receiveSync());
        if ($id <= 0) {
            throw new RelayException('unable get connection id: ' . $id);
        }
        $this->id = $id;
    }

    /**
     * @return bool
     */
    public function reconnect(): bool
    {
        try {
            $this->connection->close();
        } catch (Throwable $throwable) {
        }
        return $this->connection->connect();
    }
}
