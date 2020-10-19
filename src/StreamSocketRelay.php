<?php

namespace BitMap;

use Spiral\Goridge\Exceptions\PrefixException;
use Spiral\Goridge\Exceptions\RelayException;
use Spiral\Goridge\Exceptions\TransportException;
use Spiral\Goridge\RelayInterface;
use function Spiral\Goridge\packMessage;

class StreamSocketRelay implements RelayInterface
{
    protected $connection;

    protected $connected = false;

    protected $fp = null;

    public function __construct(string $connection)
    {
        $this->connection = $connection;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function connect(): bool
    {
        if ($this->connected) {
            return true;
        }
        $fp = stream_socket_client($this->connection, $errno, $errstr);
        if (!$fp) {
            throw new RelayException("unable to establish connection {$this->connection}: [{$errno}]{$errstr}");
        }
        $this->fp = $fp;
        $this->connected = true;
        return true;
    }

    public function close(): void
    {
        if ($this->connected) {
            fclose($this->fp);
            $this->connected = false;
            $this->fp = null;
        }
    }

    public function send(string $payload, ?int $flags = null): self
    {
        $package = packMessage($payload, $flags);
        if ($package === null) {
            throw new TransportException('unable to send payload with PAYLOAD_NONE flag');
        }
        $this->connect();
        if (fwrite($this->fp, $package['body'], 17 + $package['size']) === false) {
            throw new TransportException('unable to write payload to the stream');
        }
        return $this;
    }

    private function fetchPrefix(): array
    {
        $prefixBody = fread($this->fp, 17);
        if ($prefixBody === false || strlen($prefixBody) !== 17) {
            throw new PrefixException(sprintf(
                'unable to read prefix from socket: %s',
                $this->connection
            ));
        }

        $result = unpack('Cflags/Psize/Jrevs', $prefixBody);
        if (!is_array($result)) {
            throw new PrefixException('invalid prefix');
        }

        if ($result['size'] !== $result['revs']) {
            throw new PrefixException('invalid prefix (checksum)');
        }

        return $result;
    }

    public function receiveSync(?int &$flags = null): ?string
    {
        $this->connect();
        $prefix = $this->fetchPrefix();
        $flags = $prefix['flags'];
        $result = '';

        if ($prefix['size'] !== 0) {
            $readBytes = $prefix['size'];

            //Add ability to write to stream in a future
            while ($readBytes > 0) {
                $buffer = fread(
                    $this->fp,
                    min(self::BUFFER_SIZE, $readBytes)
                );
                if ($buffer === false) {
                    throw new PrefixException(sprintf(
                        'unable to read prefix from socket: %s',
                        $this->connection
                    ));
                }

                $result .= $buffer;
                $readBytes -= strlen($buffer);
            }
        }

        return ($result !== '') ? $result : null;
    }
}
