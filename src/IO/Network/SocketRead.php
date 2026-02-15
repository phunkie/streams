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
 * TCP socket reader as a pullable stream Resource.
 *
 * Connects lazily on first pull and reads data with a configurable timeout.
 */
class SocketRead implements Resource
{
    private $socket;
    private float $timeout;

    /**
     * @param SocketAddress $address Remote TCP address to connect to
     * @param float         $timeout Connection timeout in seconds
     */
    public function __construct(
        private SocketAddress $address,
        float $timeout = 30.0
    ) {
        $this->timeout = $timeout;
    }

    /** Close the socket if still open. */
    public function __destruct()
    {
        if ($this->isOpen()) {
            $this->close();
        }
    }

    /**
     * Create a Stream that reads all data from a TCP socket in chunks.
     *
     * @param SocketAddress $address Remote TCP address
     * @param int           $bytes   Chunk size in bytes
     * @param float         $timeout Connection timeout in seconds
     * @return Stream
     */
    public static function readAll(SocketAddress $address, int $bytes = 4096, float $timeout = 30.0): Stream
    {
        $stream = Stream(new SocketRead($address, $timeout));

        return $stream->setBytes($bytes);
    }

    /**
     * Pull the next chunk of bytes from the socket.
     *
     * Connects on first call. Returns Resource::EOF when the remote end closes.
     *
     * @param int $bytes Number of bytes to read
     * @return string Data chunk, or Resource::EOF when the connection is closed
     */
    public function pull($bytes): string
    {
        if (!$this->isOpen()) {
            $this->connect();
        }

        return $this->read($bytes);
    }

    /**
     * Check whether the socket is an open stream resource.
     *
     * @return bool
     */
    private function isOpen(): bool
    {
        return is_resource($this->socket) && get_resource_type($this->socket) === 'stream';
    }

    /**
     * Open a TCP connection to the remote address.
     *
     * @return void
     */
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

    /**
     * Read up to $bytes from the socket, returning Resource::EOF when closed.
     *
     * @param int $bytes Number of bytes to read.
     * @return string
     */
    private function read($bytes): string
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

    /**
     * Close the socket connection.
     *
     * @return void
     */
    private function close(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }
}
