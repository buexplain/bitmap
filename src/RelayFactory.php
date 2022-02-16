<?php

declare(strict_types=1);

namespace BitMap;

use Swoole\Coroutine;
use Swoole\Runtime;

class RelayFactory
{
    protected static function getConnectionAddress()
    {
        if (strtolower(PHP_OS) == 'linux') {
            return defined('BITMAP_CONNECTION_ADDRESS') ? BITMAP_CONNECTION_ADDRESS : 'unix:///run/bitmap-rpc.sock';
        }
        return defined('BITMAP_CONNECTION_ADDRESS') ? BITMAP_CONNECTION_ADDRESS : 'tcp://127.0.0.1:6060';
    }

    /**
     * @param string $connection
     * @return \BitMap\Relay
     * @throws \Spiral\Goridge\Exceptions\RelayException
     */
    public static function make(string $connection = ''): Relay
    {
        if ($connection == '') {
            $connection = static::getConnectionAddress();
        }
        if (extension_loaded('sockets')) {
            if (class_exists('\Swoole\Runtime')) {
                //存在swoole
                if (Coroutine::getCid() > 0) {
                    //开了协程
                    !defined('SWOOLE_HOOK_SOCKETS') && define('SWOOLE_HOOK_SOCKETS', 16384);
                    if ((Runtime::getHookFlags() & SWOOLE_HOOK_SOCKETS) == SWOOLE_HOOK_SOCKETS) {
                        //hook了sockets扩展
                        $relay = \Spiral\Goridge\Relay::create($connection);
                    } else {
                        $relay = new StreamSocketRelay($connection);
                    }
                } else {
                    //没开协程
                    $relay = \Spiral\Goridge\Relay::create($connection);
                }
            } else {
                //没有swoole
                $relay = \Spiral\Goridge\Relay::create($connection);
            }
        } else {
            $relay = new StreamSocketRelay($connection);
        }
        $r = new Relay();
        $r->connection = $relay;
        $r->askConnectionId();
        return $r;
    }
}
