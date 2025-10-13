<?php

namespace Phunkie\Streams\IO\Network;

use Phunkie\Streams\IO\Resource;
use Phunkie\Streams\Type\Stream;

/**
 * TCP Socket server for accepting client connections
 *
 * Each pull() returns a client socket resource
 */
class SocketServer implements Resource
{
    private $serverSocket;

    public function __construct(
        private string $host,
        private int $port,
        private int $backlog = SOMAXCONN
    ) {
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException("Port must be between 1 and 65535, got {$port}");
        }
    }

    public function __destruct()
    {
        if ($this->isOpen()) {
            $this->close();
        }
    }

    public static function listen(string $host, int $port, int $backlog = SOMAXCONN): Stream
    {
        $stream = Stream(new SocketServer($host, $port, $backlog));

        return $stream;
    }

    public function pull($bytes)
    {
        if (!$this->isOpen()) {
            $this->bind();
        }

        return $this->accept();
    }

    private function isOpen(): bool
    {
        return is_resource($this->serverSocket) && get_resource_type($this->serverSocket) === 'stream';
    }

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

    private function accept()
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

    private function close(): void
    {
        if (is_resource($this->serverSocket)) {
            fclose($this->serverSocket);
        }
    }

    public function getAddress(): string
    {
        return "tcp://{$this->host}:{$this->port}";
    }
}
