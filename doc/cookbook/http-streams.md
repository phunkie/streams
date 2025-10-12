# HTTP Streams

This section provides recipes for working with HTTP streams using Phunkie Streams.

## HTTP Request/Response

### Basic HTTP Client

**Problem**: Make HTTP requests to a server.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$response = Stream(new HttpRequest("GET", "https://api.example.com/data"))
    ->through(bracket())
    ->map(fn($response) => json_decode($response->getBody(), true))
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: This recipe demonstrates how to make HTTP requests and process responses using streams. The `bracket` pattern ensures proper resource cleanup.

### HTTP Server

**Problem**: Create an HTTP server that handles requests.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$server = Stream(new HttpServer("localhost", 8080))
    ->through(bracket())
    ->map(fn($request) => Stream($request)
        ->through(bracket())
        ->map(fn($data) => processRequest($data))
        ->compile()
        ->drain
    )
    ->compile()
    ->drain;
```

**Discussion**: This recipe creates an HTTP server that processes incoming requests. The `bracket` pattern ensures proper resource cleanup.

## Stream Context Integration

### Custom Stream Context

**Problem**: Use a custom stream context for HTTP requests.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode(['key' => 'value'])
    ]
]);

$response = Stream(new HttpRequest("POST", "https://api.example.com/data", $context))
    ->through(bracket())
    ->map(fn($response) => json_decode($response->getBody(), true))
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: Custom stream contexts allow you to configure HTTP requests with specific options like headers and content.

## REST API Clients

### REST API Client

**Problem**: Create a REST API client for a service.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

class RestApiClient
{
    private $baseUrl;
    
    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }
    
    public function get($endpoint)
    {
        return Stream(new HttpRequest("GET", $this->baseUrl . $endpoint))
            ->through(bracket())
            ->map(fn($response) => json_decode($response->getBody(), true))
            ->compile()
            ->toList()
            ->unsafeRunSync();
    }
    
    public function post($endpoint, $data)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data)
            ]
        ]);
        
        return Stream(new HttpRequest("POST", $this->baseUrl . $endpoint, $context))
            ->through(bracket())
            ->map(fn($response) => json_decode($response->getBody(), true))
            ->compile()
            ->toList()
            ->unsafeRunSync();
    }
}

$client = new RestApiClient("https://api.example.com");
$data = $client->get("/users");
```

**Discussion**: This recipe shows how to create a REST API client that uses streams for HTTP requests.

## WebSocket Support

### WebSocket Server

**Problem**: Create a WebSocket server for real-time communication.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$server = Stream(new WebSocketServer("localhost", 8080))
    ->through(bracket())
    ->map(fn($client) => Stream($client)
        ->through(bracket())
        ->map(fn($message) => processMessage($message))
        ->compile()
        ->drain
    )
    ->compile()
    ->drain;
```

**Discussion**: WebSocket servers enable real-time, bidirectional communication between clients and servers.

### WebSocket Client

**Problem**: Create a WebSocket client to connect to a server.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$client = Stream(new WebSocketClient("ws://localhost:8080"))
    ->through(bracket())
    ->map(fn($message) => "Hello, Server!")
    ->compile()
    ->drain;
```

**Discussion**: WebSocket clients can send and receive messages in real-time.

## Best Practices

1. **Resource Management**
   - Always use `bracket` or `Scope` for HTTP operations
   - Implement proper connection cleanup
   - Handle request timeouts

2. **Error Handling**
   - Use `Validation` for HTTP operations
   - Handle HTTP errors
   - Implement retry mechanisms

3. **Performance**
   - Use appropriate buffer sizes
   - Implement backpressure mechanisms
   - Consider using connection pooling

4. **Security**
   - Validate HTTP data
   - Implement proper authentication
   - Use HTTPS for sensitive data

## See Also

- [Resource Management](../resource-management.md)
- [Error Handling](../error-handling.md)
- [Advanced Topics](../advanced-topics.md) 