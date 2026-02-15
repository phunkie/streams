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
 * HTTP request as a pullable stream Resource.
 *
 * Supports GET, POST, PUT, DELETE, PATCH, and HEAD methods.
 * The request is executed lazily on the first pull. Uses PHP stream
 * contexts internally.
 */
class HttpRequest implements Resource
{
    private $handle;
    private bool $executed = false;

    /**
     * @param string      $url     Request URL
     * @param string      $method  HTTP method (GET, POST, PUT, DELETE, PATCH, HEAD)
     * @param array       $headers Request headers as strings or key-value pairs
     * @param string|null $body    Request body (used with POST, PUT, PATCH)
     * @param float       $timeout Connection timeout in seconds
     * @throws \InvalidArgumentException If the HTTP method is not supported
     */
    public function __construct(
        private string $url,
        private string $method = 'GET',
        private array $headers = [],
        private ?string $body = null,
        private float $timeout = 30.0
    ) {
        if (!in_array(strtoupper($method), ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD'])) {
            throw new \InvalidArgumentException("Invalid HTTP method: {$method}");
        }
    }

    /** Close the response stream if still open. */
    public function __destruct()
    {
        if ($this->isOpen()) {
            $this->close();
        }
    }

    /**
     * Create a Stream for an HTTP GET request.
     *
     * @param string $url     Request URL
     * @param array  $headers Optional request headers
     * @return Stream
     */
    public static function get(string $url, array $headers = []): Stream
    {
        return Stream(new HttpRequest($url, 'GET', $headers));
    }

    /**
     * Create a Stream for an HTTP POST request.
     *
     * @param string $url     Request URL
     * @param string $body    Request body
     * @param array  $headers Optional request headers
     * @return Stream
     */
    public static function post(string $url, string $body, array $headers = []): Stream
    {
        return Stream(new HttpRequest($url, 'POST', $headers, $body));
    }

    /**
     * Create a Stream for an HTTP PUT request.
     *
     * @param string $url     Request URL
     * @param string $body    Request body
     * @param array  $headers Optional request headers
     * @return Stream
     */
    public static function put(string $url, string $body, array $headers = []): Stream
    {
        return Stream(new HttpRequest($url, 'PUT', $headers, $body));
    }

    /**
     * Create a Stream for an HTTP DELETE request.
     *
     * @param string $url     Request URL
     * @param array  $headers Optional request headers
     * @return Stream
     */
    public static function delete(string $url, array $headers = []): Stream
    {
        return Stream(new HttpRequest($url, 'DELETE', $headers));
    }

    /**
     * Pull the next chunk of response data.
     *
     * Executes the HTTP request on first call. Returns Resource::EOF
     * when the response has been fully consumed.
     *
     * @param int $bytes Number of bytes to read
     * @return string Data chunk, or Resource::EOF when the response is exhausted
     */
    public function pull($bytes): string
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->read($bytes);
    }

    /**
     * Check whether the response handle is an open stream resource.
     *
     * @return bool
     */
    private function isOpen(): bool
    {
        return is_resource($this->handle) && get_resource_type($this->handle) === 'stream';
    }

    /**
     * Execute the HTTP request and open the response stream.
     *
     * @return void
     */
    private function execute(): void
    {
        $options = [
            'http' => [
                'method' => strtoupper($this->method),
                'header' => $this->formatHeaders(),
                'timeout' => $this->timeout,
                'ignore_errors' => true, // Get response even on error status codes
            ],
        ];

        if ($this->body !== null && in_array(strtoupper($this->method), ['POST', 'PUT', 'PATCH'])) {
            $options['http']['content'] = $this->body;
        }

        $context = stream_context_create($options);
        $this->handle = @fopen($this->url, 'r', false, $context);

        if ($this->handle === false) {
            throw new \RuntimeException("Failed to execute HTTP {$this->method} request to {$this->url}");
        }

        $this->executed = true;
    }

    /**
     * Format the headers array into an HTTP header string.
     *
     * @return string
     */
    private function formatHeaders(): string
    {
        if (empty($this->headers)) {
            return '';
        }

        // Headers can be passed as array of strings or key-value pairs
        $formatted = [];
        foreach ($this->headers as $key => $value) {
            if (is_int($key)) {
                // Already formatted header like "Content-Type: application/json"
                $formatted[] = $value;
            } else {
                // Key-value pair
                $formatted[] = "{$key}: {$value}";
            }
        }

        return implode("\r\n", $formatted);
    }

    /**
     * Read up to $bytes from the response, returning Resource::EOF at end-of-stream.
     *
     * @param int $bytes Number of bytes to read.
     * @return string
     */
    private function read($bytes): string
    {
        if (!is_resource($this->handle)) {
            throw new \Error("HTTP handle is not a valid resource");
        }

        $data = fread($this->handle, $bytes);

        if ($data === false || ($data === '' && feof($this->handle))) {
            return Resource::EOF;
        }

        return $data;
    }

    /**
     * Close the response stream handle.
     *
     * @return void
     */
    private function close(): void
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    /**
     * Get response headers from the HTTP request.
     *
     * Only available after the request has been executed (after the first pull).
     *
     * @return array Response headers, or empty array if not yet executed
     */
    public function getResponseHeaders(): array
    {
        if (!$this->executed || !$this->isOpen()) {
            return [];
        }

        $metadata = stream_get_meta_data($this->handle);

        return $metadata['wrapper_data'] ?? [];
    }
}
