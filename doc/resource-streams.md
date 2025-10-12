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
use function Phunkie\Streams\Stream;
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
use function Phunkie\Streams\Stream;
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

Phunkie Streams can work with network resources like HTTP requests and socket connections.

### HTTP Requests

```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\io\httpGet;

// Create a stream from an HTTP GET request
$responseStream = Stream(httpGet("https://api.example.com/data"))
    ->compile()
    ->toList()
    ->unsafeRunSync();

// Process JSON from an API
$processedData = Stream(httpGet("https://api.example.com/users"))
    ->map(fn($response) => json_decode($response, true))
    ->flatMap(fn($data) => $data['users'])
    ->filter(fn($user) => $user['active'] === true)
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

### Socket Connections

```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\io\socket;

// Create a stream from a socket
$socketStream = Stream(socket("localhost", 8080))
    ->through(fn($line) => "Processed: " . $line)
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

## Resource Management with bracket()

The `bracket()` function is a crucial part of Phunkie Streams' resource management system. It implements the "bracket pattern" (also known as "try-with-resources" in some languages) which ensures proper resource cleanup even in the presence of errors.

### How bracket() Works

```php
Stream(new Path("file.txt"))
    ->through(bracket())
    ->map(fn($line) => processLine($line))
    ->compile()
    ->drain;
```

The `bracket()` function:

1. **Acquires** a resource (opens a file, establishes a connection, etc.)
2. **Uses** the resource for processing
3. **Releases** the resource when done, even if an error occurs

This is equivalent to the following imperative code:

```php
try {
    $resource = acquireResource();
    try {
        useResource($resource);
    } finally {
        releaseResource($resource);
    }
} catch (Exception $e) {
    handleError($e);
}
```

### Why bracket() is Important

1. **Resource Safety**: Ensures resources are always released, preventing resource leaks
2. **Error Handling**: Properly handles errors during resource acquisition and usage
3. **Composability**: Can be composed with other stream operations
4. **Declarative Style**: Expresses resource management in a functional way

### Common Use Cases

```php
// File handling
Stream(new Path("file.txt"))
    ->through(bracket())
    ->map(fn($line) => processLine($line));

// HTTP requests
Stream(new HttpRequest("GET", "https://api.example.com"))
    ->through(bracket())
    ->map(fn($response) => processResponse($response));

// Database connections
Stream(new DatabaseConnection("mysql://localhost/db"))
    ->through(bracket())
    ->map(fn($conn) => executeQuery($conn));

// Process management
Stream(new Process("command"))
    ->through(bracket())
    ->map(fn($output) => processOutput($output));
```

### bracket() vs. Manual Resource Management

```php
// Manual resource management (error-prone)
$handle = fopen("file.txt", "r");
try {
    while (($line = fgets($handle)) !== false) {
        processLine($line);
    }
} finally {
    fclose($handle);
}

// Using bracket() (safe and declarative)
Stream(new Path("file.txt"))
    ->through(bracket())
    ->map(fn($line) => processLine($line))
    ->compile()
    ->drain;
```

The `bracket()` pattern is essential for writing robust streaming applications that handle resources safely and efficiently. 

## Resource Safety

One of the key benefits of using Phunkie Streams with resources is automatic resource management. Phunkie Streams ensures that resources are properly acquired and released, even in the presence of errors.

### Using Bracket for Resource Safety

The `bracket` operation ensures that a resource is always properly closed, regardless of whether the operations succeed or fail:

```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\io\bracket;
use Phunkie\Streams\IO\File\Path;

// Safe file processing with bracket
$result = bracket(
    // Resource acquisition
    fn() => fopen("data.txt", "r"),
    // Resource usage
    fn($handle) => Stream(function() use ($handle) {
        while (!feof($handle)) {
            yield fgets($handle);
        }
    })
    ->map(fn($line) => trim($line))
    ->filter(fn($line) => !empty($line)),
    // Resource release
    fn($handle) => fclose($handle)
)
->compile()
->toList()
->unsafeRunSync();
```

### Scope and Resource Management

Phunkie Streams uses the concept of a Scope to manage resource lifetimes:

```php
<?php
use function Phunkie\Streams\Stream;
use Phunkie\Streams\Scope\ResourceScope;
use Phunkie\Streams\IO\File\Path;

// Create a resource scope
$scope = new ResourceScope();

// Register resources with the scope
$fileStream = Stream(new Path("data.txt"))
    ->through($scope->register(fn() => $resource))
    ->compile()
    ->toList();

// All resources in the scope will be released when the scope is closed
$scope->close();
```

## Error Handling

Resource operations can fail for various reasons (file not found, network issues, etc.). Phunkie Streams provides tools for handling these errors in a functional way:

```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\io\attempt;
use Phunkie\Streams\IO\File\Path;
use function Phunkie\PatternMatching\Referenced\{Success, Failure};
use function pmatch;

// Use attempt to catch exceptions and convert them to Validation values
$result = Stream(new Path("possibly-missing-file.txt"))
    ->through(attempt())
    ->compile()
    ->toList()
    ->unsafeRunSync();

$on = pmatch($result);
match(true) {
    $on(Success($data)) => printf("Success! Got data: %d lines\n", count($data)),
    $on(Failure($error)) => printf("Failed with error: %s\n", $error->getMessage())
};
```

## Best Practices for Resource Streams

When working with resource streams, follow these best practices:

1. **Always use `.compile()`** before terminal operations on resource streams
2. **Always use `.unsafeRunSync()`** to execute IO operations
3. **Use bracket or scope** for proper resource cleanup
4. **Handle errors** using `attempt()` or similar error-handling mechanisms
5. **Be mindful of memory usage** when processing large files
6. **Set appropriate buffer sizes** for optimal performance

## Composing Resource Operations

One of the strengths of Phunkie Streams is the ability to compose complex resource operations:

```php
<?php
use function Phunkie\Streams\Stream;
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
