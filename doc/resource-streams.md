# Resource Streams

Resource streams are a powerful feature of Phunkie Streams that allow you to work with I/O resources like files, network connections, and other external data sources in a functional, safe, and composable way.

## What are Resource Streams?

Resource streams represent sequences of data that come from or go to external resources. Unlike pure streams that operate on in-memory values, resource streams:

- Connect to external resources (files, network sockets, etc.)
- Perform effectful operations (reading, writing)
- Require proper resource management (acquisition and release)
- Return IO-wrapped results to indicate their effectful nature

## Working with Files

Phunkie Streams provides a clean API for working with files as streams.

### Reading Files

You can create streams from files to process their contents line by line or byte by byte:

```php
<?php
use Phunkie\Streams\IO\File\Path;

// Create a stream from a file
$fileStream = Stream(new Path("data.csv"));

// Process the file line by line
$processedData = $fileStream
    ->map(fn($line) => str_getcsv($line))
    ->filter(fn($row) => count($row) > 0)
    ->map(fn($row) => [
        'name' => $row[0],
        'value' => (int)$row[1]
    ])
    ->compile()
    ->toList()
    ->unsafeRunSync();

// Reading a file with a specific buffer size (in bytes)
$largeFileStream = Stream(new Path("large_file.txt"), 4096);
```

### Writing to Files

You can also create streams that write data to files:

```php
<?php
use function Phunkie\Streams\Functions\file\writeFile;
use Phunkie\Streams\IO\File\Path;

// Create an array of data
$data = ["Line 1", "Line 2", "Line 3"];

// Write data to a file
$writeResult = Stream($data)
    ->through(writeFile(new Path("output.txt")))
    ->compile()
    ->drain
    ->unsafeRunSync();
```

### File Operations

Resource streams support various file operations:

```php
<?php
use function Phunkie\Streams\Functions\file\exists;
use function Phunkie\Streams\Functions\file\deleteFile;
use Phunkie\Streams\IO\File\Path;

// Check if a file exists
$fileExists = exists(new Path("data.txt"))->unsafeRunSync();

// Delete a file
$deleteResult = deleteFile(new Path("temp.txt"))->unsafeRunSync();

// Copy a file using streams
$copyResult = Stream(new Path("source.txt"))
    ->through(writeFile(new Path("destination.txt")))
    ->compile()
    ->drain
    ->unsafeRunSync();
```

## Network Resources

Phunkie Streams provides comprehensive network operations for HTTP requests and TCP sockets.

### HTTP Requests

```php
<?php
use Phunkie\Streams\Network;

// Simple HTTP GET request
$data = Network::httpGet('https://api.example.com/data')
    ->compile->toArray();

// Process JSON from an API
$users = Network::httpGet('https://api.example.com/users')
    ->map(fn($chunk) => json_decode($chunk, true))
    ->filter(fn($data) => $data !== null)
    ->compile->toArray();

// HTTP POST with JSON payload
$response = Network::httpPost(
    'https://api.example.com/users',
    json_encode(['name' => 'Alice', 'email' => 'alice@example.com']),
    ['Content-Type: application/json']
)->compile->toArray();
```

### TCP Socket Connections

```php
<?php
use Phunkie\Streams\{Network, IO\Network\SocketAddress};

// TCP client - connect and read
$messages = Network::client(new SocketAddress('localhost', 8080))
    ->take(10)
    ->map(fn($data) => trim($data))
    ->compile->toArray();

// TCP server - accept connections
Network::server(host: '0.0.0.0', port: 8080)
    ->map(function($clientSocket) {
        $data = fread($clientSocket, 1024);
        fwrite($clientSocket, "Echo: $data");
        fclose($clientSocket);
        return "Handled client";
    })
    ->take(5) // Handle 5 clients
    ->compile->drain
    ->unsafeRunSync();

// Write stream to socket
Stream(...['message1', 'message2', 'message3'])
    ->through(Network::socketWrite(new SocketAddress('localhost', 8080)));
```

## Resource Management

Phunkie Streams uses two complementary approaches for resource management:

1. **Automatic cleanup via `__destruct()`** - Resource objects (HttpRequest, SocketRead, etc.) automatically clean up when no longer referenced
2. **Explicit cleanup via `bracket()`** - For operations requiring guaranteed immediate cleanup

### Using bracket() for File I/O

The `bracket()` function ensures proper resource cleanup even in the presence of errors:

```php
<?php
use function Phunkie\Streams\Functions\file\{readFileContents, writeFileContents};
use Phunkie\Streams\IO\File\Path;

// Read file with automatic cleanup
$content = readFileContents(new Path('data.txt'))
    ->map(fn($text) => strtoupper($text))
    ->unsafeRunSync();

// Write file with automatic cleanup
$bytes = writeFileContents(new Path('output.txt'), "Hello, World!")
    ->unsafeRunSync();
```

These file I/O functions use bracket() internally for guaranteed cleanup.

### Manual bracket() Usage

For custom resource management:

```php
<?php
use function Phunkie\Streams\Functions\resource\bracket;
use function Phunkie\Effect\Functions\io\io;

$result = bracket(
    // Acquire resource
    io(fn() => fopen('data.txt', 'r')),
    // Use resource
    fn($handle) => io(fn() => stream_get_contents($handle)),
    // Release resource (always called, even on errors)
    fn($handle) => io(fn() => fclose($handle))
)->unsafeRunSync();
```

### Automatic Resource Management

Network and stream resources use PHP's `__destruct()` for automatic cleanup:

```php
<?php
use Phunkie\Streams\Network;

// HttpRequest automatically closes when stream completes
$data = Network::httpGet('https://api.example.com/data')
    ->map(fn($chunk) => process($chunk))
    ->compile->toArray();
// Connection automatically closed here

// SocketRead automatically closes when done
$messages = Network::client(new SocketAddress('localhost', 8080))
    ->take(10)
    ->compile->toArray();
// Socket automatically closed here
```

For a comprehensive guide on when to use each pattern, see [Resource Management Guide](resource-management.md). 

## Resource Safety Guarantees

Phunkie Streams ensures resources are properly managed through:

1. **PHP's deterministic garbage collection** - Resources cleaned up immediately when last reference is dropped
2. **`__destruct()` methods** - All Resource classes implement proper cleanup in destructors
3. **`bracket()` function** - Guarantees finalization even when errors occur

### Examples of Safe Resource Handling

```php
<?php
use function Phunkie\Streams\Functions\file\{readLines, writeLines};
use Phunkie\Streams\IO\File\Path;

// File operations are always safe - bracket() used internally
$lines = readLines(new Path('data.txt'))
    ->map(fn($linesArray) => array_map('trim', $linesArray))
    ->unsafeRunSync();

// Even if an error occurs, files are properly closed
try {
    writeLines(new Path('/invalid/path.txt'), ['line1', 'line2'])
        ->unsafeRunSync();
} catch (\Exception $e) {
    // File handle still properly closed, no leak
    echo "Error: " . $e->getMessage();
}
```

### Stream-Based Resource Cleanup

Stream resources automatically clean up when consumed:

```php
<?php
use Phunkie\Streams\Network;

try {
    // Even if processing throws, HttpRequest's __destruct() ensures cleanup
    $data = Network::httpGet('https://api.example.com/data')
        ->map(fn($chunk) => riskyOperation($chunk))
        ->compile->toArray();
} catch (\Exception $e) {
    // HTTP connection already closed by __destruct()
    echo "Processing failed but connection closed safely";
}
```

## Error Handling

Resource operations can fail for various reasons (file not found, network issues, etc.). Phunkie Streams provides functional error handling through IO's `attempt()` and `handleError()`:

```php
<?php
use function Phunkie\Streams\Functions\file\readFileContents;
use Phunkie\Streams\IO\File\Path;

// Using attempt() - returns Validation<Throwable, string>
$result = readFileContents(new Path('/nonexistent/file.txt'))
    ->attempt()
    ->unsafeRunSync();

$content = $result->getOrElse("default content");

// Using handleError() - recover from errors
$content = readFileContents(new Path('/nonexistent/file.txt'))
    ->handleError(fn($e) => "Error: " . $e->getMessage())
    ->unsafeRunSync();

// Network error handling
$data = Network::httpGet('https://invalid-domain.example.com')
    ->map(fn($chunk) => process($chunk))
    ->attempt()  // Returns Validation
    ->unsafeRunSync()
    ->getOrElse([]);  // Default empty array on error
```

For comprehensive error handling patterns, see [Error Handling Guide](error-handling.md).

## Best Practices for Resource Streams

When working with resource streams, follow these best practices:

1. **Use the Network API for HTTP/sockets** - `Network::httpGet()`, `Network::client()`, etc.
2. **Use file I/O functions for files** - `readFileContents()`, `writeFileContents()`, `readLines()`, `writeLines()`
3. **Always use `.compile()` before terminal operations** on resource streams
4. **Always use `.unsafeRunSync()`** to execute IO operations
5. **Handle errors functionally** using `attempt()` or `handleError()`
6. **Trust automatic cleanup** - Resource objects clean themselves up via `__destruct()`
7. **Use bracket() for raw resources** - When working with file handles or raw sockets directly
8. **Be mindful of memory** when processing large files - use appropriate buffer sizes

## Composing Resource Operations

One of the strengths of Phunkie Streams is the ability to compose complex resource operations:

```php
<?php
use Phunkie\Streams\IO\File\Path;

// Complex resource operation: read from one file, transform, and write to another
$result = Stream(new Path("input.csv"))
    ->map(fn($line) => str_getcsv($line))
    ->filter(fn($row) => !empty($row[0]))
    ->map(fn($row) => [
        'id' => $row[0],
        'name' => $row[1],
        'value' => (int)$row[2]
    ])
    ->filter(fn($item) => $item['value'] > 100)
    ->map(fn($item) => json_encode($item))
    ->through(writeFile(new Path("output.json")))
    ->compile()
    ->drain
    ->unsafeRunSync();
```

By following these patterns, you can work with external resources in a functional, composable way that maintains the safety and expressiveness of Phunkie Streams.
