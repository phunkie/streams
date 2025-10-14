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

use Phunkie\Streams\IO\Resource;
use Phunkie\Streams\Type\Stream;

/**
 * TCP Socket client for reading data from a remote server
 */
class SocketRead implements Resource
{
    private $socket;
    private float $timeout;

    public function __construct(
        private SocketAddress $address,
        float $timeout = 30.0
    ) {
        $this->timeout = $timeout;
    }

    public function __destruct()
    {
        if ($this->isOpen()) {
            $this->close();
        }
    }

    public static function readAll(SocketAddress $address, int $bytes = 4096, float $timeout = 30.0): Stream
    {
        $stream = Stream(new SocketRead($address, $timeout));

        return $stream->setBytes($bytes);
    }

    public function pull($bytes)
    {
        if (!$this->isOpen()) {
            $this->connect();
        }

        return $this->read($bytes);
    }

    private function isOpen(): bool
    {
        return is_resource($this->socket) && get_resource_type($this->socket) === 'stream';
    }

    private function connect(): void
    {
        $errno = 0;
        $errstr = '';

        $this->socket = @stream_socket_client(
            $this->address->toString(),
            $errno,
            $errstr,
            $this->timeout
        );

        if ($this->socket === false) {
            throw new \RuntimeException(
                "Failed to connect to {$this->address->toString()}: [{$errno}] {$errstr}"
            );
        }

        // Set socket to non-blocking mode for better stream handling
        stream_set_blocking($this->socket, false);
    }

    private function read($bytes)
    {
        if (!is_resource($this->socket)) {
            throw new \Error("Socket is not a valid resource");
        }

        // Set back to blocking for read
        stream_set_blocking($this->socket, true);
        $data = fread($this->socket, $bytes);
        stream_set_blocking($this->socket, false);

        if ($data === false || ($data === '' && feof($this->socket))) {
            return Resource::EOF;
        }

        return $data;
    }

    private function close(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }
}
