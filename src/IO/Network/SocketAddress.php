<?php

namespace Phunkie\Streams\IO\Network;

/**
 * Represents a socket address (host:port combination)
 */
class SocketAddress
{
    public function __construct(
        private readonly string $host,
        private readonly int $port
    ) {
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException("Port must be between 1 and 65535, got {$port}");
        }
    }

    public function toString(): string
    {
        return "tcp://{$this->host}:{$this->port}";
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
