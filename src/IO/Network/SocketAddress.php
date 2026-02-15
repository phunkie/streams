<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\IO\Network;

/**
 * Value object representing a TCP socket address (host:port).
 *
 * Port is validated to be within the range 1-65535.
 */
class SocketAddress
{
    /**
     * @param string $host Hostname or IP address
     * @param int    $port TCP port (1-65535)
     * @throws \InvalidArgumentException If port is out of range
     */
    public function __construct(
        private readonly string $host,
        private readonly int $port
    ) {
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException("Port must be between 1 and 65535, got {$port}");
        }
    }

    /**
     * @return string The address formatted as a TCP URI (e.g. "tcp://host:port")
     */
    public function toString(): string
    {
        return "tcp://{$this->host}:{$this->port}";
    }

    /**
     * @return string The hostname or IP address
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int The port number
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return string The address formatted as a TCP URI
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
