<?php

namespace BitMap;

class RPCFactory
{
    public static function make(): RPC
    {
        return new RPC(RelayFactory::make());
    }
}
