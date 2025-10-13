# Network Streams

This section provides recipes for working with TCP sockets using Phunkie Streams' Network API.

## TCP Streams

### Basic TCP Server

**Problem**: Create a TCP server that handles client connections.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

Network::server(host: 'localhost', port: 8080)
    ->map(function($clientSocket) {
        // Read data from client
        $data = fread($clientSocket, 1024);

        // Process and respond
        $response = processClientData($data);
        fwrite($clientSocket, $response);

        // Close connection
        fclose($clientSocket);

        return "Handled client";
    })
    ->take(10) // Handle 10 clients then stop
    ->compile->drain
    ->unsafeRunSync();
```

**Discussion**: `Network::server()` creates a stream of client connections. Each client socket is automatically managed - you're responsible for reading, writing, and closing within the map function.

### Echo Server

**Problem**: Create a simple echo server that sends back received data.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

Network::server(host: '0.0.0.0', port: 8080)
    ->map(function($clientSocket) {
        $data = fread($clientSocket, 1024);
        $message = trim($data);

        echo "Received: $message\n";

        fwrite($clientSocket, "Echo: $message\n");
        fclose($clientSocket);

        return $message;
    })
    ->compile->drain
    ->unsafeRunSync();
```

**Discussion**: This creates a simple echo server listening on all interfaces.

### TCP Client

**Problem**: Connect to a TCP server and read data.

**Solution**:
```php
<?php
use Phunkie\Streams\{Network, IO\Network\SocketAddress};

$messages = Network::client(new SocketAddress('localhost', 8080))
    ->take(10)
    ->map(fn($data) => trim($data))
    ->compile->toArray();

foreach ($messages as $message) {
    echo "Received: $message\n";
}
```

**Discussion**: `Network::client()` connects to a server and streams data from it. The connection is automatically cleaned up when the stream completes.

### Writing to a Socket

**Problem**: Send data to a TCP server.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use Phunkie\Streams\{Network, IO\Network\SocketAddress};

Stream('message1', 'message2', 'message3')
    ->through(Network::socketWrite(new SocketAddress('localhost', 8080)));
```

**Discussion**: `Network::socketWrite()` is a pipe function that writes stream elements to a socket connection.

## Chat Server Example

### Multi-Client Chat Server

**Problem**: Build a chat server that broadcasts messages to all clients.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

class ChatServer
{
    private array $clients = [];
    private int $nextId = 1;

    public function run(): void
    {
        echo "Chat server starting on port 8080...\n";

        Network::server(host: '0.0.0.0', port: 8080)
            ->map(fn($client) => $this->handleClient($client))
            ->compile->drain
            ->unsafeRunSync();
    }

    private function handleClient($clientSocket): string
    {
        $clientId = $this->nextId++;
        $this->clients[$clientId] = $clientSocket;

        echo "Client $clientId connected\n";

        // Send welcome message
        fwrite($clientSocket, "Welcome! You are client $clientId\n");

        // Read message
        $data = fread($clientSocket, 1024);
        $message = trim($data);

        if (!empty($message)) {
            echo "Client $clientId: $message\n";
            $this->broadcast($clientId, $message);
        }

        // Cleanup
        unset($this->clients[$clientId]);
        fclose($clientSocket);

        return "Client $clientId disconnected";
    }

    private function broadcast(int $senderId, string $message): void
    {
        foreach ($this->clients as $id => $client) {
            if ($id !== $senderId) {
                fwrite($client, "Client $senderId: $message\n");
            }
        }
    }
}

$server = new ChatServer();
$server->run();
```

**Discussion**: This example shows how to manage multiple client connections and broadcast messages.

## Data Processing Patterns

### Line-Based Protocol

**Problem**: Implement a line-based protocol (like Redis or Memcached).

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

class LineProtocolServer
{
    public function run(): void
    {
        Network::server(host: 'localhost', port: 6379)
            ->map(fn($client) => $this->handleCommand($client))
            ->compile->drain
            ->unsafeRunSync();
    }

    private function handleCommand($clientSocket): string
    {
        $command = trim(fgets($clientSocket));
        $parts = explode(' ', $command);
        $cmd = strtoupper($parts[0] ?? '');

        $response = match($cmd) {
            'PING' => "+PONG\r\n",
            'ECHO' => '+' . ($parts[1] ?? '') . "\r\n",
            'GET' => $this->handleGet($parts[1] ?? ''),
            'SET' => $this->handleSet($parts[1] ?? '', $parts[2] ?? ''),
            default => "-ERR unknown command\r\n"
        };

        fwrite($clientSocket, $response);
        fclose($clientSocket);

        return "Command: $cmd";
    }

    private function handleGet(string $key): string
    {
        // Implement GET logic
        return "-ERR not implemented\r\n";
    }

    private function handleSet(string $key, string $value): string
    {
        // Implement SET logic
        return "+OK\r\n";
    }
}

$server = new LineProtocolServer();
$server->run();
```

**Discussion**: Demonstrates implementing a simple command-response protocol.

### Binary Protocol

**Problem**: Handle binary data over TCP.

**Solution**:
```php
<?php
use Phunkie\Streams\{Network, IO\Network\SocketAddress};

// Server: receive binary data
Network::server(host: 'localhost', port: 9000)
    ->map(function($client) {
        // Read 4-byte length header
        $lengthData = fread($client, 4);
        $length = unpack('N', $lengthData)[1];

        // Read payload
        $payload = fread($client, $length);

        // Process binary data
        $processed = processBinaryData($payload);

        // Send response
        $responseLength = pack('N', strlen($processed));
        fwrite($client, $responseLength . $processed);

        fclose($client);
        return "Processed $length bytes";
    })
    ->compile->drain
    ->unsafeRunSync();

// Client: send binary data
$data = pack('N', 5) . 'Hello'; // 4-byte length + data
Network::socketWrite(new SocketAddress('localhost', 9000))
    (Stream($data));
```

**Discussion**: Shows how to handle binary protocols with length prefixes.

## Error Handling

### Connection Timeout

**Problem**: Handle connection timeouts gracefully.

**Solution**:
```php
<?php
use Phunkie\Streams\{Network, IO\Network\SocketAddress};

$connectWithTimeout = function(SocketAddress $address, int $timeout = 5) {
    return Network::client($address)
        ->take(1)
        ->attempt()
        ->map(function($result) {
            if ($result->isSuccess()) {
                return $result->getOrElse([]);
            } else {
                echo "Connection failed: " . $result->getError()->getMessage() . "\n";
                return [];
            }
        })
        ->unsafeRunSync();
};

$data = $connectWithTimeout(new SocketAddress('localhost', 8080), 5);
```

**Discussion**: Use `attempt()` to handle connection failures gracefully.

### Server Error Recovery

**Problem**: Keep server running even when client handling fails.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

Network::server(host: 'localhost', port: 8080)
    ->map(function($client) {
        try {
            $data = fread($client, 1024);
            $result = processData($data);
            fwrite($client, $result);
            fclose($client);
            return "Success";
        } catch (\Exception $e) {
            fclose($client);
            echo "Error handling client: {$e->getMessage()}\n";
            return "Error";
        }
    })
    ->compile->drain
    ->unsafeRunSync();
```

**Discussion**: Wrap client handling in try-catch to prevent one failed client from crashing the server.

## Performance Patterns

### Connection Limits

**Problem**: Limit concurrent connections to prevent resource exhaustion.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

Network::server(host: 'localhost', port: 8080)
    ->take(100) // Only handle 100 connections total
    ->map(function($client) {
        $data = fread($client, 1024);
        fwrite($client, "Received\n");
        fclose($client);
        return "Handled";
    })
    ->compile->drain
    ->unsafeRunSync();
```

**Discussion**: Use `take()` to limit total number of connections handled.

### Buffered Reading

**Problem**: Read large amounts of data efficiently.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

Network::server(host: 'localhost', port: 8080)
    ->map(function($client) {
        $buffer = '';
        $bufferSize = 4096;

        while (!feof($client)) {
            $chunk = fread($client, $bufferSize);
            if ($chunk === false) break;
            $buffer .= $chunk;

            // Stop if we have a complete message
            if (str_contains($buffer, "\n")) {
                break;
            }
        }

        $response = processMessage(trim($buffer));
        fwrite($client, $response . "\n");
        fclose($client);

        return "Processed " . strlen($buffer) . " bytes";
    })
    ->compile->drain
    ->unsafeRunSync();
```

**Discussion**: Read in chunks until a complete message is received.

## Testing Network Code

### Mock Server for Testing

**Problem**: Test TCP client code without a real server.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

// Start a mock server in the background for testing
$mockServer = function() {
    Network::server(host: 'localhost', port: 9999)
        ->map(function($client) {
            fwrite($client, "test response\n");
            fclose($client);
            return "sent";
        })
        ->take(1)
        ->compile->drain
        ->unsafeRunSync();
};

// Run mock server in background (use & or separate process in real code)
// Then test your client code
$response = Network::client(new SocketAddress('localhost', 9999))
    ->take(1)
    ->compile->toArray();

assert($response[0] === "test response\n");
```

**Discussion**: Create lightweight mock servers for testing network client code.

## Best Practices

1. **Resource Management**
   - Network resources clean up automatically via `__destruct()`
   - Always close client sockets in server handlers with `fclose()`
   - Use `take()` to limit connections and prevent infinite loops
   - Trust automatic cleanup for client connections

2. **Error Handling**
   - Use `attempt()` for operations that may fail
   - Wrap client handling in try-catch for servers
   - Log connection errors for debugging
   - Keep servers running despite individual client failures

3. **Performance**
   - Use appropriate buffer sizes (4096 is good default)
   - Read in chunks for large data transfers
   - Limit concurrent connections with `take()`
   - Consider connection pooling for Phase 6 (not yet implemented)

4. **Security**
   - Validate all data received from network
   - Implement authentication mechanisms
   - Use TLS/SSL for sensitive data (via stream contexts)
   - Sanitize input to prevent injection attacks
   - Limit message sizes to prevent DoS

5. **Protocol Design**
   - Use length prefixes for binary protocols
   - Use delimiters (newlines) for text protocols
   - Version your protocol for future compatibility
   - Document protocol specification clearly

## Limitations

**UDP Support**: Phunkie Streams currently only provides TCP socket support via `Network::server()` and `Network::client()`. UDP sockets are not yet implemented.

**Connection Pooling**: Connection pooling is planned for Phase 6 but not yet available.

**Backpressure**: Flow control mechanisms are planned for Phase 6 but not yet implemented.

## See Also

- [HTTP Streams](http-streams.md) - HTTP-specific recipes
- [Resource Management](../resource-management.md) - Resource patterns
- [Error Handling](../error-handling.md) - Comprehensive error handling
- [Resource Streams](../resource-streams.md) - Deep dive into network I/O
- [Getting Started](../getting-started.md#tcp-sockets) - Network basics
