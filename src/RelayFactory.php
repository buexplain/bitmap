<?php

declare(strict_types=1);

namespace BitMap;

use Spiral\Goridge\Exceptions\RelayException;

class RelayFactory
{
    /**
     * @var string[] connection info
     */
    protected static $connection = [
        'tcp'=>'tcp://127.0.0.1:37101',
        'unix'=>'unix:///tmp/bitmap-rpc.sock',
    ];

    public static function make(string $connection=''): Relay
    {
        if ($connection == '') {
            $connection = strtolower(PHP_OS) == 'linux' ? static::$connection['unix'] : static::$connection['tcp'];
        }
        if (class_exists('\Swoole\Coroutine') && \Swoole\Coroutine::getCid() > 0) {
            //swoole协程模式下采用可被协程调度的stream_socket_client客户端
            $relay = new StreamSocketRelay($connection);
        } else {
            //否则采用 socket_create，避免swoole环境下被hook掉，同时也能兼容fpm环境
            $relay = \Spiral\Goridge\Relay::create($connection);
        }
        //丢弃头部信息
        $header = (string) $relay->receiveSync();
        //读取本次连接的id
        $connectionID = intval($relay->receiveSync());
        if ($connectionID <= 0) {
            throw new RelayException('unable get connection id: ' . $connection);
        }
        $r = new Relay();
        $r->connection = $relay;
        $r->id = $connectionID;
        return $r;
    }
}
