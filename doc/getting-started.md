# Getting Started with Phunkie Streams

This guide will help you get up and running with Phunkie Streams in your PHP projects.

## Installation

Phunkie Streams can be installed via Composer, the PHP package manager.

### Requirements

- PHP 8.2 or higher
- Composer

### Installing via Composer

```bash
composer require phunkie/streams
```

This will add Phunkie Streams to your project and create the necessary autoload configurations.

### Verifying Installation

To verify that Phunkie Streams is installed correctly, you can create a simple test script:

```php
<?php
require 'vendor/autoload.php';

// Create a simple stream
$stream = Stream(1, 2, 3);
var_dump($stream->toArray()); // Should output [1, 2, 3]
```

## Basic Usage

### Creating Streams

Phunkie Streams provides several ways to create streams:

```php
<?php
require 'vendor/autoload.php';

// Import the necessary functions
use function Phunkie\Streams\Infinite\fromRange;
use function Phunkie\Streams\Infinite\iterate;

// From values
$stream1 = Stream(1, 2, 3, 4, 5);

// From arrays
$array = ['a', 'b', 'c'];
$stream2 = Stream(...$array);

// From ranges (infinite)
$stream3 = Stream(fromRange(1, 100));

// From iteration (infinite)
$stream4 = Stream(iterate(1)(fn($x) => $x * 2));

// From files
use Phunkie\Streams\IO\File\Path;
$filePath = new Path('path/to/your/file.txt');
$fileStream = Stream($filePath);
```

### Transforming Streams

Streams can be transformed using various operations:

```php
<?php
// Map: apply a function to each element
$doubled = Stream(1, 2, 3, 4)
    ->map(fn($x) => $x * 2)
    ->toArray(); // [2, 4, 6, 8]

// Filter: keep only elements that match a predicate
$evens = Stream(1, 2, 3, 4, 5, 6)
    ->filter(fn($x) => $x % 2 === 0)
    ->toArray(); // [2, 4, 6]

// Take: limit to first n elements
$first3 = Stream(1, 2, 3, 4, 5)
    ->take(3)
    ->compile()
    ->toArray(); // [1, 2, 3]

// Drop: skip first n elements
$afterFirst2 = Stream(1, 2, 3, 4, 5)
    ->drop(2)
    ->compile()
    ->toArray(); // [3, 4, 5]

// TakeWhile: take elements while condition is true
$lessThan4 = Stream(1, 2, 3, 4, 5, 1, 2)
    ->takeWhile(fn($x) => $x < 4)
    ->toArray(); // [1, 2, 3]

// DropWhile: skip elements while condition is true
$fromThree = Stream(1, 2, 3, 4, 5)
    ->dropWhile(fn($x) => $x < 3)
    ->toArray(); // [3, 4, 5]

// Chunk: process in batches
$chunked = Stream(1, 2, 3, 4, 5, 6)
    ->chunk(2)
    ->toArray(); // [[1, 2], [3, 4], [5, 6]]
```

### Using Pipes with through()

Compose reusable transformation pipelines:

```php
<?php
// Define reusable transformations
$onlyEven = fn($s) => $s->filter(fn($x) => $x % 2 === 0);
$double = fn($s) => $s->map(fn($x) => $x * 2);

// Apply them with through()
$result = Stream(1, 2, 3, 4, 5)
    ->through($onlyEven)
    ->through($double)
    ->toArray(); // [4, 8]
```

### Consuming Streams

Streams provide several ways to consume their elements:

```php
<?php
$stream = Stream(1, 2, 3, 4, 5);

// Convert to array
$array = $stream->toArray(); // [1, 2, 3, 4, 5]

// Convert to List (Phunkie collection type)
$list = $stream->toList(); // List(1, 2, 3, 4, 5)

// Process each element
$stream->foreach(function($element) {
    echo "Processing: $element\n";
});

// Reduce to a single value
$sum = $stream
    ->fold(0)(fn($acc, $x) => $acc + $x);
// 15 (1 + 2 + 3 + 4 + 5)
```

### Handling Infinite Streams

Infinite streams must be limited before consumption:

```php
<?php
// Create an infinite stream of natural numbers
$naturals = Stream(fromRange(1));

// Take only the first 10 elements
$first10 = $naturals
    ->take(10)
    ->compile()
    ->toArray(); // [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]

// Filter and take operations on infinite streams
$first5Evens = Stream(fromRange(1))
    ->filter(fn($x) => $x % 2 === 0)
    ->take(5)
    ->compile()
    ->toArray(); // [2, 4, 6, 8, 10]
```

## Working with Files

Phunkie Streams provides safe file I/O operations:

```php
<?php
use Phunkie\Streams\IO\File\Path;
use function Phunkie\Streams\IO\File\{readFileContents, writeFileContents, readLines, writeLines};

// Read entire file
$content = readFileContents(new Path('data.txt'))
    ->unsafeRunSync();

// Write to file
$bytes = writeFileContents(new Path('output.txt'), "Hello, World!")
    ->unsafeRunSync();

// Read file as lines
$lines = readLines(new Path('data.csv'))
    ->map(fn($linesArray) => array_map('trim', $linesArray))
    ->unsafeRunSync();

// Write lines to file
writeLines(new Path('output.txt'), ['line1', 'line2', 'line3'])
    ->unsafeRunSync();

// Stream from file (line by line)
$processedLines = Stream(new Path('data.csv'))
    ->map(fn($line) => str_getcsv($line))
    ->filter(fn($row) => !empty($row[0]))
    ->compile()
    ->toArray();
```

### File I/O with Pipes

Use the `writeFile()` pipe to write streams to files:

```php
<?php
use function Phunkie\Streams\IO\File\writeFile;

// Write stream to file
Stream('line1', 'line2', 'line3')
    ->through(writeFile(new Path('output.txt')));

// Transform and write
Stream(1, 2, 3, 4, 5)
    ->filter(fn($x) => $x % 2 === 0)
    ->map(fn($x) => "Even: $x")
    ->through(writeFile(new Path('evens.txt')));
```

## Working with Network Resources

### HTTP Requests

Make HTTP requests as streams:

```php
<?php
use Phunkie\Streams\Network;

// Simple HTTP GET
$response = Network::httpGet('https://api.example.com/data')
    ->compile->toArray();

// Process JSON from API
$data = Network::httpGet('https://api.example.com/users')
    ->map(fn($chunk) => json_decode($chunk, true))
    ->filter(fn($data) => $data !== null)
    ->compile->toArray();

// HTTP POST with JSON
$response = Network::httpPost(
    'https://api.example.com/users',
    json_encode(['name' => 'Alice', 'email' => 'alice@example.com']),
    ['Content-Type: application/json']
)->compile->toArray();
```

### TCP Sockets

Work with TCP sockets:

```php
<?php
use Phunkie\Streams\{Network, IO\Network\SocketAddress};

// TCP client - connect and read
$messages = Network::client(new SocketAddress('localhost', 8080))
    ->take(10)
    ->compile->toArray();

// TCP server - accept connections
Network::server(host: 'localhost', port: 8080)
    ->map(function($clientSocket) {
        $data = fread($clientSocket, 1024);
        fwrite($clientSocket, "Echo: $data");
        fclose($clientSocket);
        return "Handled client";
    })
    ->take(5)
    ->compile->drain
    ->unsafeRunSync();
```

## Error Handling

Handle errors functionally with `attempt()` and `handleError()`:

```php
<?php
use function Phunkie\Streams\IO\File\readFileContents;

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
    ->attempt()
    ->unsafeRunSync()
    ->getOrElse([]);  // Default empty array on error
```

See [Error Handling Guide](error-handling.md) for comprehensive patterns.

## Resource Management

Phunkie Streams handles resources safely:

- **File I/O**: All file functions (`readFileContents()`, `writeFileContents()`, etc.) use the bracket pattern internally for guaranteed cleanup
- **Network resources**: HTTP and socket resources clean up automatically via `__destruct()`
- **Custom resources**: Use `bracket()` from phunkie/effect for your own resources

```php
<?php
use function Phunkie\Streams\Functions\resource\bracket;
use function Phunkie\Effect\Functions\io\io;

// Custom resource with bracket
$result = bracket(
    // Acquire
    io(fn() => fopen('data.txt', 'r')),
    // Use
    fn($handle) => io(fn() => stream_get_contents($handle)),
    // Release (always called, even on errors)
    fn($handle) => io(fn() => fclose($handle))
)->unsafeRunSync();
```

See [Resource Management Guide](resource-management.md) for detailed patterns.

## Configuration

### Setting Stream Buffer Size

You can configure the buffer size for file stream operations:

```php
<?php
use Phunkie\Streams\IO\File\Path;

// Default buffer size (4096 bytes)
$stream = Stream(new Path('file.txt'));

// Custom buffer size (8192 bytes)
$stream = Stream(new Path('large-file.txt'), 8192);

// Large buffer for high-throughput
$stream = Stream(new Path('huge-file.dat'), 65536);
```

### Performance Tips

To optimize performance with Phunkie Streams:

1. **Choose appropriate buffer sizes** - Larger buffers for binary data, smaller for text
2. **Use lazy operations** - Streams are lazy by default, leveraging this for large datasets
3. **Limit infinite streams early** - Apply `take()` as early as possible
4. **Trust automatic cleanup** - File and network resources clean up automatically
5. **Use pipes for composition** - `through()` creates reusable transformation pipelines

## Next Steps

Now that you've learned the basics of Phunkie Streams, you can explore more advanced topics:

- [Core Concepts](core-concepts.md) - Understand the fundamental abstractions
- [Working with Streams](working-with-streams.md) - Learn more about stream operations
- [Pure Streams](pure-streams.md) - Dive deeper into pure functional streams
- [Infinite Streams](infinite-streams.md) - Master working with unbounded data
- [Resource Streams](resource-streams.md) - Deep dive into file and network I/O
- [Advanced Topics](advanced-topics.md) - Complex patterns and best practices
- [Error Handling Guide](error-handling.md) - Comprehensive error handling patterns
- [Composition Guide](composition.md) - Monadic composition with flatMap()

## Examples

Check out the examples directory for working code:

- [examples/bracket.php](../examples/bracket.php) - Resource management (10 examples)
- [examples/error-handling.php](../examples/error-handling.php) - Error handling (12 examples)
- [examples/composition.php](../examples/composition.php) - Stream composition (12 examples)
- [examples/stream-operations.php](../examples/stream-operations.php) - Stream operations (20 examples)
- [examples/file-pipes.php](../examples/file-pipes.php) - File I/O with streams (12 examples)
- [examples/network.php](../examples/network.php) - Network operations (15 examples)
