# Network Streams

This section provides recipes for working with network streams using Phunkie Streams.

## TCP Streams

### Basic TCP Server

**Problem**: Create a TCP server that handles client connections.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$server = Stream(new TcpServer("localhost", 8080))
    ->through(bracket())
    ->map(fn($client) => Stream($client)
        ->through(bracket())
        ->map(fn($data) => processClientData($data))
        ->compile()
        ->drain
    )
    ->compile()
    ->drain;
```

**Discussion**: This recipe creates a TCP server that accepts client connections and processes their data. The `bracket` pattern ensures proper resource cleanup for both the server and client connections.

### TCP Client

**Problem**: Create a TCP client to connect to a server.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$client = Stream(new TcpClient("localhost", 8080))
    ->through(bracket())
    ->map(fn($data) => "Hello, Server!")
    ->compile()
    ->drain;
```

**Discussion**: The client connects to the server and sends data. The `bracket` pattern ensures the connection is properly closed.

## UDP Streams

### UDP Server

**Problem**: Create a UDP server for connectionless communication.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$server = Stream(new UdpServer("localhost", 8080))
    ->through(bracket())
    ->map(fn($packet) => processPacket($packet))
    ->compile()
    ->drain;
```

**Discussion**: UDP servers handle individual packets without maintaining connections. This is useful for real-time applications where connection overhead is not desired.

### UDP Client

**Problem**: Send UDP packets to a server.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$client = Stream(new UdpClient("localhost", 8080))
    ->through(bracket())
    ->map(fn($data) => createPacket($data))
    ->compile()
    ->drain;
```

**Discussion**: UDP clients send individual packets without establishing a connection. This is useful for broadcasting or when connection overhead is not desired.

## Half and Full Duplex Sockets

### Half Duplex Communication

**Problem**: Implement half-duplex communication where data flows in one direction at a time.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$server = Stream(new HalfDuplexServer("localhost", 8080))
    ->through(bracket())
    ->map(fn($client) => Stream($client)
        ->through(bracket())
        ->map(fn($data) => processData($data))
        ->compile()
        ->drain
    )
    ->compile()
    ->drain;
```

**Discussion**: Half-duplex communication ensures that data flows in one direction at a time, preventing data collisions.

### Full Duplex Communication

**Problem**: Implement full-duplex communication where data can flow in both directions simultaneously.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$server = Stream(new FullDuplexServer("localhost", 8080))
    ->through(bracket())
    ->map(fn($client) => Stream($client)
        ->through(bracket())
        ->map(fn($data) => processData($data))
        ->compile()
        ->drain
    )
    ->compile()
    ->drain;
```

**Discussion**: Full-duplex communication allows data to flow in both directions simultaneously, enabling more efficient communication.

## Network Protocols

### Custom Protocol Implementation

**Problem**: Implement a custom network protocol.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$server = Stream(new CustomProtocolServer("localhost", 8080))
    ->through(bracket())
    ->map(fn($client) => Stream($client)
        ->through(bracket())
        ->map(fn($data) => processProtocolData($data))
        ->compile()
        ->drain
    )
    ->compile()
    ->drain;
```

**Discussion**: Custom protocols can be implemented by defining the data format and processing logic.

## Connection Management

### Connection Pooling

**Problem**: Manage a pool of connections for efficient resource usage.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$pool = new ConnectionPool(10); // Maximum 10 connections
$server = Stream(new TcpServer("localhost", 8080))
    ->through(bracket())
    ->map(fn($client) => $pool->acquire($client))
    ->map(fn($connection) => Stream($connection)
        ->through(bracket())
        ->map(fn($data) => processData($data))
        ->compile()
        ->drain
    )
    ->compile()
    ->drain;
```

**Discussion**: Connection pooling helps manage resources efficiently by reusing connections.

## Best Practices

1. **Resource Management**
   - Always use `bracket` or `Scope` for network operations
   - Implement proper connection cleanup
   - Handle connection timeouts

2. **Error Handling**
   - Use `Validation` for network operations
   - Handle connection errors
   - Implement retry mechanisms

3. **Performance**
   - Use appropriate buffer sizes
   - Implement backpressure mechanisms
   - Consider using connection pooling

4. **Security**
   - Validate network data
   - Implement proper authentication
   - Use encryption for sensitive data

## See Also

- [Resource Management](../resource-management.md)
- [Error Handling](../error-handling.md)
- [Advanced Topics](../advanced-topics.md) 