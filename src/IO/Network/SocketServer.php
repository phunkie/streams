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
 * TCP server listener as a pullable stream Resource.
 *
 * Binds to a host:port, listens for connections, and yields accepted
 * client socket resources on each pull.
 */
class SocketServer implements Resource
{
    private $serverSocket;

    /**
     * @param string $host    Hostname or IP to bind to
     * @param int    $port    TCP port to listen on (1-65535)
     * @param int    $backlog Maximum length of the pending connections queue
     * @throws \InvalidArgumentException If port is out of range
     */
    public function __construct(
        private string $host,
        private int $port,
        private int $backlog = SOMAXCONN
    ) {
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException("Port must be between 1 and 65535, got {$port}");
        }
    }

    /** Close the server socket if still open. */
    public function __destruct()
    {
        if ($this->isOpen()) {
            $this->close();
        }
    }

    /**
     * Create a Stream that listens for incoming TCP connections.
     *
     * @param string $host    Hostname or IP to bind to
     * @param int    $port    TCP port to listen on
     * @param int    $backlog Maximum pending connections queue length
     * @return Stream
     */
    public static function listen(string $host, int $port, int $backlog = SOMAXCONN): Stream
    {
        $stream = Stream(new SocketServer($host, $port, $backlog));

        return $stream;
    }

    /**
     * Accept the next client connection.
     *
     * Binds and listens on first call. Returns a client socket resource,
     * or Resource::EOF if the accept fails.
     *
     * @param int $bytes Unused (required by Resource interface)
     * @return mixed Client socket resource, or Resource::EOF on failure
     */
    public function pull($bytes): mixed
    {
        if (!$this->isOpen()) {
            $this->bind();
        }

        return $this->accept();
    }

    /** Check whether the server socket is an open stream resource. */
    private function isOpen(): bool
    {
        return is_resource($this->serverSocket) && get_resource_type($this->serverSocket) === 'stream';
    }

    /** Create and bind a TCP server socket, then start listening. */
    private function bind(): void
    {
        $errno = 0;
        $errstr = '';
        $address = "tcp://{$this->host}:{$this->port}";

        $this->serverSocket = @stream_socket_server(
            $address,
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
        );

        if ($this->serverSocket === false) {
            throw new \RuntimeException(
                "Failed to bind server to {$address}: [{$errno}] {$errstr}"
            );
        }

        // Set to non-blocking for better handling
        stream_set_blocking($this->serverSocket, false);
    }

    /**
     * Accept the next incoming connection, returning the client socket or Resource::EOF.
     */
    private function accept(): mixed
    {
        if (!is_resource($this->serverSocket)) {
            throw new \Error("Server socket is not a valid resource");
        }

        // Block while accepting
        stream_set_blocking($this->serverSocket, true);
        $client = stream_socket_accept($this->serverSocket, -1);
        stream_set_blocking($this->serverSocket, false);

        if ($client === false) {
            return Resource::EOF;
        }

        return $client;
    }

    /** Close the server socket. */
    private function close(): void
    {
        if (is_resource($this->serverSocket)) {
            fclose($this->serverSocket);
        }
    }

    /**
     * @return string The bound address as a TCP URI (e.g. "tcp://host:port")
     */
    public function getAddress(): string
    {
        return "tcp://{$this->host}:{$this->port}";
    }
}
