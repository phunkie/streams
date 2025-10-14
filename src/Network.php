<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams;

use Phunkie\Effect\IO\IO;

use function Phunkie\Streams\IO\Network\httpDelete as _httpDelete;
use function Phunkie\Streams\IO\Network\httpGet as _httpGet;
use function Phunkie\Streams\IO\Network\httpPost as _httpPost;
use function Phunkie\Streams\IO\Network\httpPut as _httpPut;
use function Phunkie\Streams\IO\Network\socket as _socket;

use Phunkie\Streams\IO\Network\SocketAddress;

use function Phunkie\Streams\IO\Network\socketRead as _socketRead;
use function Phunkie\Streams\IO\Network\socketServer as _socketServer;
use function Phunkie\Streams\IO\Network\socketWrite as _socketWrite;

use Phunkie\Streams\Type\Stream;

/**
 * Network operations for streams
 *
 * Provides clean static API for network operations including HTTP requests
 * and TCP sockets (both client and server).
 *
 * @example HTTP GET
 * Network::httpGet('https://api.example.com/data')
 *     ->compile->toArray()
 *     ->unsafeRunSync();
 *
 * @example TCP Client
 * Network::client(new SocketAddress('localhost', 8080))
 *     ->map(fn($data) => process($data))
 *     ->compile->toArray()
 *     ->unsafeRunSync();
 *
 * @example TCP Server
 * Network::server(host: 'localhost', port: 8080)
 *     ->map(fn($client) => handleClient($client))
 *     ->compile->drain
 *     ->unsafeRunSync();
 */
class Network
{
    /**
     * Create a TCP server that accepts client connections
     *
     * Each element in the stream is a client socket resource that can be used
     * to read/write data to/from the client.
     *
     * @param string $host Host to bind to (default '0.0.0.0' for all interfaces)
     * @param int $port Port to listen on
     * @param int $backlog Maximum number of queued connections
     * @return Stream Stream of client socket resources
     *
     * @example
     * Network::server(host: 'localhost', port: 8080)
     *     ->map(function($client) {
     *         $data = fread($client, 1024);
     *         fwrite($client, "Echo: $data");
     *         fclose($client);
     *     })
     *     ->take(10)
     *     ->compile->drain
     *     ->unsafeRunSync();
     */
    public static function server(
        string $host = '0.0.0.0',
        int $port = 8080,
        int $backlog = SOMAXCONN
    ): Stream {
        return _socketServer($host, $port, $backlog);
    }

    /**
     * Create a TCP client connection as a stream
     *
     * Returns a stream that reads data from the remote socket.
     *
     * @param SocketAddress $address The socket address to connect to
     * @param int $bufferSize Buffer size for reading (default 4096 bytes)
     * @param float $timeout Connection timeout in seconds (default 30.0)
     * @return Stream Stream of data from the socket
     *
     * @example
     * Network::client(new SocketAddress('localhost', 8080))
     *     ->map(fn($data) => processData($data))
     *     ->compile->toArray()
     *     ->unsafeRunSync();
     */
    public static function client(
        SocketAddress $address,
        int $bufferSize = 4096,
        float $timeout = 30.0
    ): Stream {
        return _socketRead($address, $bufferSize, $timeout);
    }

    /**
     * Create a socket connection with bracket for safe resource management
     *
     * Returns an IO action that creates a socket connection and ensures
     * it's properly closed even on errors.
     *
     * @param SocketAddress $address The socket address to connect to
     * @param float $timeout Connection timeout in seconds
     * @return IO<resource> IO action containing the socket resource
     *
     * @example
     * Network::socket(new SocketAddress('localhost', 8080))
     *     ->flatMap(fn($socket) => io(fn() => fwrite($socket, "Hello\n")))
     *     ->unsafeRunSync();
     */
    public static function socket(SocketAddress $address, float $timeout = 30.0): IO
    {
        return _socket($address, $timeout);
    }

    /**
     * Write stream data to a TCP socket (pipe function)
     *
     * Returns a pipe function that can be used with through() to write
     * stream elements to a socket.
     *
     * @param SocketAddress $address The socket address to connect to
     * @param float $timeout Connection timeout in seconds
     * @return callable Pipe function for use with through()
     *
     * @example
     * Stream(...['line1', 'line2', 'line3'])
     *     ->through(Network::socketWrite(new SocketAddress('localhost', 8080)));
     */
    public static function socketWrite(SocketAddress $address, float $timeout = 30.0): callable
    {
        return _socketWrite($address, $timeout);
    }

    /**
     * HTTP GET request as a stream
     *
     * @param string $url The URL to request
     * @param array $headers Optional HTTP headers
     * @return Stream Stream of response data
     *
     * @example
     * Network::httpGet('https://api.example.com/data')
     *     ->compile->toArray()
     *     ->unsafeRunSync();
     */
    public static function httpGet(string $url, array $headers = []): Stream
    {
        return _httpGet($url, $headers);
    }

    /**
     * HTTP POST request as a stream
     *
     * @param string $url The URL to request
     * @param string $body The request body
     * @param array $headers Optional HTTP headers
     * @return Stream Stream of response data
     *
     * @example
     * Network::httpPost(
     *     'https://api.example.com/users',
     *     json_encode(['name' => 'Alice']),
     *     ['Content-Type: application/json']
     * )->compile->toArray()->unsafeRunSync();
     */
    public static function httpPost(string $url, string $body, array $headers = []): Stream
    {
        return _httpPost($url, $body, $headers);
    }

    /**
     * HTTP PUT request as a stream
     *
     * @param string $url The URL to request
     * @param string $body The request body
     * @param array $headers Optional HTTP headers
     * @return Stream Stream of response data
     */
    public static function httpPut(string $url, string $body, array $headers = []): Stream
    {
        return _httpPut($url, $body, $headers);
    }

    /**
     * HTTP DELETE request as a stream
     *
     * @param string $url The URL to request
     * @param array $headers Optional HTTP headers
     * @return Stream Stream of response data
     */
    public static function httpDelete(string $url, array $headers = []): Stream
    {
        return _httpDelete($url, $headers);
    }
}
