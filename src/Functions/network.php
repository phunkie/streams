<?php

namespace Phunkie\Streams\IO\Network {

    use Phunkie\Effect\IO\IO;
    use Phunkie\Streams\IO\Network\HttpRequest;
    use Phunkie\Streams\IO\Network\SocketAddress;
    use Phunkie\Streams\IO\Network\SocketRead;
    use Phunkie\Streams\IO\Network\SocketServer;
    use Phunkie\Streams\Type\Stream;
    use function Phunkie\Effect\Functions\io\io;
    use function Phunkie\Streams\Functions\resource\bracket;

    /**
     * HTTP GET request as a stream
     *
     * @param string $url The URL to request
     * @param array $headers Optional HTTP headers
     * @return Stream Stream of response data
     */
    function httpGet(string $url, array $headers = []): Stream
    {
        return HttpRequest::get($url, $headers);
    }

    /**
     * HTTP POST request as a stream
     *
     * @param string $url The URL to request
     * @param string $body The request body
     * @param array $headers Optional HTTP headers
     * @return Stream Stream of response data
     */
    function httpPost(string $url, string $body, array $headers = []): Stream
    {
        return HttpRequest::post($url, $body, $headers);
    }

    /**
     * HTTP PUT request as a stream
     *
     * @param string $url The URL to request
     * @param string $body The request body
     * @param array $headers Optional HTTP headers
     * @return Stream Stream of response data
     */
    function httpPut(string $url, string $body, array $headers = []): Stream
    {
        return HttpRequest::put($url, $body, $headers);
    }

    /**
     * HTTP DELETE request as a stream
     *
     * @param string $url The URL to request
     * @param array $headers Optional HTTP headers
     * @return Stream Stream of response data
     */
    function httpDelete(string $url, array $headers = []): Stream
    {
        return HttpRequest::delete($url, $headers);
    }

    /**
     * Create a TCP socket connection with bracket for safe resource management
     *
     * @param SocketAddress $address The socket address to connect to
     * @return IO<resource> IO action that creates a socket connection
     */
    function socket(SocketAddress $address, float $timeout = 30.0): IO
    {
        return bracket(
            io(function() use ($address, $timeout) {
                $errno = 0;
                $errstr = '';
                $socket = stream_socket_client(
                    $address->toString(),
                    $errno,
                    $errstr,
                    $timeout
                );
                if ($socket === false) {
                    throw new \RuntimeException(
                        "Failed to connect to {$address->toString()}: [{$errno}] {$errstr}"
                    );
                }
                return $socket;
            }),
            fn($socket) => io(fn() => $socket),
            fn($socket) => io(fn() => fclose($socket))
        );
    }

    /**
     * Read from a TCP socket as a stream
     *
     * @param SocketAddress $address The socket address to connect to
     * @param int $bufferSize Buffer size for reads (default 4096)
     * @param float $timeout Connection timeout in seconds
     * @return Stream Stream of data from the socket
     */
    function socketRead(SocketAddress $address, int $bufferSize = 4096, float $timeout = 30.0): Stream
    {
        return SocketRead::readAll($address, $bufferSize, $timeout);
    }

    /**
     * Write stream data to a TCP socket (pipe function)
     *
     * @param SocketAddress $address The socket address to connect to
     * @param float $timeout Connection timeout in seconds
     * @return callable Pipe function that writes stream to socket
     */
    function socketWrite(SocketAddress $address, float $timeout = 30.0): callable
    {
        return function(Stream $stream) use ($address, $timeout): Stream {
            $errno = 0;
            $errstr = '';
            $socket = stream_socket_client(
                $address->toString(),
                $errno,
                $errstr,
                $timeout
            );

            if ($socket === false) {
                throw new \RuntimeException(
                    "Failed to connect to {$address->toString()}: [{$errno}] {$errstr}"
                );
            }

            try {
                foreach ($stream->toArray() as $data) {
                    fwrite($socket, strval($data) . PHP_EOL);
                }
            } finally {
                fclose($socket);
            }

            return Stream();
        };
    }

    /**
     * Create a TCP server that accepts connections
     *
     * @param string $host Host to bind to (default '0.0.0.0' for all interfaces)
     * @param int $port Port to listen on
     * @param int $backlog Maximum queued connections
     * @return Stream Stream of client socket resources
     */
    function socketServer(string $host, int $port, int $backlog = SOMAXCONN): Stream
    {
        return SocketServer::listen($host, $port, $backlog);
    }
}
