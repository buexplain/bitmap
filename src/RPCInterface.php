<?php


namespace BitMap;

interface RPCInterface
{
    public function call(string $method, $payload, int $flags = 0);

    public function getID(): array;
}
