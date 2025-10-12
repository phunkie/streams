<?php

namespace Phunkie\Streams\IO\Network;

use Phunkie\Streams\IO\Resource;
use Phunkie\Streams\Type\Stream;

/**
 * HTTP Request resource for making HTTP calls
 */
class HttpRequest implements Resource
{
    private $handle;
    private bool $executed = false;

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

    public function __destruct()
    {
        if ($this->isOpen()) {
            $this->close();
        }
    }

    public static function get(string $url, array $headers = []): Stream
    {
        return Stream(new HttpRequest($url, 'GET', $headers));
    }

    public static function post(string $url, string $body, array $headers = []): Stream
    {
        return Stream(new HttpRequest($url, 'POST', $headers, $body));
    }

    public static function put(string $url, string $body, array $headers = []): Stream
    {
        return Stream(new HttpRequest($url, 'PUT', $headers, $body));
    }

    public static function delete(string $url, array $headers = []): Stream
    {
        return Stream(new HttpRequest($url, 'DELETE', $headers));
    }

    public function pull($bytes)
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->read($bytes);
    }

    private function isOpen(): bool
    {
        return is_resource($this->handle) && get_resource_type($this->handle) === 'stream';
    }

    private function execute(): void
    {
        $options = [
            'http' => [
                'method' => strtoupper($this->method),
                'header' => $this->formatHeaders(),
                'timeout' => $this->timeout,
                'ignore_errors' => true, // Get response even on error status codes
            ]
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

    private function read($bytes)
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

    private function close(): void
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    /**
     * Get response headers from the HTTP request
     * Only available after execution
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
