<?php

declare(strict_types=1);

namespace BitMap;

class ClientFactory
{
    /**
     * @var ClientFactory|null
     */
    protected static ?ClientFactory $instance = null;

    /**
     * @return ClientFactory
     */
    public static function getInstance(): ClientFactory
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @return Client
     */
    public function get(): Client
    {
        return new Client();
    }

    /**
     * @return Client
     */
    public static function make(): Client
    {
        return static::getInstance()->get();
    }
}
