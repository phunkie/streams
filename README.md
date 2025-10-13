# Phunkie Streams #

Phunkie Streams is a PHP functional library for working with streams inspired by functional streaming libraries like fs2 (Scala). It allows you to process data in a declarative, composable way.

## Installation

```
composer require phunkie/streams
```

## Features

- **Pure streams**: Finite sequences of values that can be transformed and combined
- **Infinite streams**: Unbounded sequences that can be processed lazily
- **Effectful operations**: Operations that interact with the outside world (I/O, etc.) using phunkie/effect
- **Resource management**: Safe handling of resources through bracket pattern for guaranteed cleanup
- **Error handling**: Functional error handling with `attempt()` and `handleError()`
- **Monadic composition**: Compose IO operations with `flatMap()` for type-safe pipelines
- **Stream operations**: Rich set of operations including `through()`, `takeWhile()`, `dropWhile()`, `chunk()`
- **Memory efficient**: True streaming with constant memory usage regardless of data size
- **Parallel processing**: Concurrent operations with automatic fallback and memory optimization

## What's Implemented

Phunkie Streams has completed Phases 1-3 of development. Here's what's currently available:

### Core Stream Types
- **Pure Streams** - `Stream(1, 2, 3)` for finite in-memory sequences
- **Infinite Streams** - `Stream(iterate(...))`, `Stream(fromRange(...))` for unbounded sequences
- **Resource Streams** - File and network I/O streams with automatic resource management

### Stream Operations
- **Transformations**: `map()`, `filter()`, `flatMap()`
- **Composition**: `concat()`, `merge()`, `interleave()`, `zip()`, `zipWith()`
- **Control flow**: `take()`, `drop()`, `takeWhile()`, `dropWhile()`
- **Batching**: `chunk()` for processing in fixed-size chunks
- **Pipes**: `through()` for composable transformation pipelines
- **Compilation**: `compile()->toArray()`, `compile()->toList()`, `compile->drain`

### File I/O
- **Reading**: `Stream(new Path('file.txt'))`, `readFileContents()`, `readLines()`
- **Writing**: `writeFileContents()`, `writeLines()`, `writeFile()` pipe function
- **Utilities**: `exists()`, `deleteFile()`
- All file operations use `bracket()` internally for guaranteed cleanup

### Network Operations
- **HTTP**: `Network::httpGet()`, `Network::httpPost()`, `Network::httpPut()`, `Network::httpDelete()`
- **TCP Client**: `Network::client(SocketAddress)` for socket connections
- **TCP Server**: `Network::server(host:, port:)` for accepting connections
- **Socket Writing**: `Network::socketWrite()` pipe function
- All network resources use automatic cleanup via `__destruct()`

### Resource Management
- **Bracket pattern**: `bracket(acquire, use, release)` from phunkie/effect
- **Automatic cleanup**: Resource objects (HttpRequest, SocketRead, etc.) clean up automatically
- **Safe by default**: All provided file/network functions handle resources safely
- See [Resource Management Guide](doc/resource-management.md) for patterns

### Error Handling
- **Validation**: `IO->attempt()` converts exceptions to `Validation<Throwable, A>`
- **Recovery**: `IO->handleError(fn)` for error recovery with fallback values
- **Composition**: Chain error handling with `flatMap()` for complex scenarios
- See [Error Handling Guide](doc/error-handling.md) for comprehensive patterns

### Concurrency & Parallel Processing
- **Parallel operations**: `parMap()`, `parMapValidation()`, `parEvalMap()`, `parTraverse()`, `parEval()`
- **Concurrent merging**: `parMerge()`, `parMergeMap()` for parallel stream combining
- **Auto CPU detection**: Automatically detects CPU cores for optimal parallelism
- **Automatic fallback**: Falls back to sequential execution on concurrency failures
- **Memory optimized**: All parallel operations process incrementally without materializing entire streams

### Not Yet Implemented
- **Backpressure**: Flow control mechanisms (planned for future release)
- **Connection pooling**: Resource pooling for HTTP/sockets (planned for future release)
- **Process integration**: System command execution (future consideration)

## Pure Streams

```php
// Stream<Pure, Int>
$stream = Stream(1, 2, 3, 4);

// Pure streams can be converted to other collections
$stream->toList();   // List<Int> (1, 2, 3, 4)
$stream->toArray();  // [1, 2, 3, 4]
```

## Transformations on Pure Streams

```php
use const Phunkie\Functions\numbers\increment;

$stream = Stream(1, 2, 3, 4);

$stream->map(increment)->toList();
// List<Int> (2, 3, 4, 5)

Stream(1, 2, 3, 4)
    ->zipWith(increment)
    ->toList();
// List(Pair(1, 2), Pair(2, 3), Pair(3, 4), Pair(4, 5))
```

## Infinite Streams

```php
// Stream from a range
$fromRange = Stream(fromRange(0, 1000000000));
$fromRange->take(10)->compile()->toList();
// List(0, 1, 2, 3, 4, 5, 6, 7, 8, 9) 

// Stream from an iteration
$infiniteOdds = Stream(iterate(1)(fn ($x) => $x + 2));
$infiniteOdds->take(10)->compile()->toList();
// List(1, 3, 5, 7, 9, 11, 13, 15, 17, 19)

// Repeating a finite stream infinitely
$repeat = Stream(1, 2, 3)
    ->repeat()->take(12)->compile()->toList();
// List(1, 2, 3, 1, 2, 3, 1, 2, 3, 1, 2, 3)
```

## Picking on Infinite Streams with runlog

```php
Stream(1, 2, 3)
    ->repeat()
    ->runLog()
    ->unsafeRun();
// [1, 2, 3, 1, 2, 3, 1, 2, 3, ...] 
```

## Combining Streams

```php
// Concatenation
Stream(1, 2, 3)
    ->concat(Stream(4, 5, 6))
    ->compile()
    ->toList();
// List(1, 2, 3, 4, 5, 6)

// Interleaving
$x = Stream(1, 2, 3, 4, 5);
$y = Stream("Monday", "Tuesday", "Wednesday", "Thursday", "Friday");
$z = Stream(true, false, true, false, true);

$x->interleave($y, $z)->compile()->toList();
// List(1, "Monday", true, 2, "Tuesday", false, 3, "Wednesday", true, 4,
// "Thursday", false, 5, "Friday", true)
```

## Basic Usage

```php
<?php

require 'vendor/autoload.php';

// Import the necessary functions
use function Phunkie\Streams\Stream;

// Create a stream
$stream = Stream(1, 2, 3, 4, 5);

// Process the stream
$result = $stream
    ->map(fn($x) => $x * 2)
    ->filter(fn($x) => $x > 5)
    ->toArray();

// Output: [6, 8, 10]
var_dump($result);
```

## Resource Management with Bracket

Phunkie Streams uses the bracket pattern from phunkie/effect for safe resource management:

```php
use Phunkie\Streams\IO\File\Path;
use function Phunkie\Streams\IO\File\{readFileContents, writeFileContents};

// Read file with automatic resource cleanup
$content = readFileContents(new Path('data.txt'))
    ->unsafeRunSync();

// Write file with guaranteed cleanup even on errors
$bytes = writeFileContents(new Path('output.txt'), "Hello, World!")
    ->unsafeRunSync();
```

See [examples/bracket.php](examples/bracket.php) for more examples.

## Error Handling

Functional error handling with `attempt()` and `handleError()`:

```php
use function Phunkie\Streams\IO\File\readFileContents;

// Using attempt() - returns Validation
$result = readFileContents(new Path('/nonexistent/file.txt'))
    ->attempt()
    ->unsafeRunSync();

$content = $result->getOrElse("default content");

// Using handleError() - recover from errors
$content = readFileContents(new Path('/nonexistent/file.txt'))
    ->handleError(fn($e) => "Error: " . $e->getMessage())
    ->unsafeRunSync();
```

See [examples/error-handling.php](examples/error-handling.php) and [doc/error-handling.md](doc/error-handling.md) for comprehensive patterns.

## Stream Composition with flatMap

Compose IO operations in type-safe pipelines:

```php
use function Phunkie\Streams\IO\File\{readFileContents, writeFileContents};

$result = writeFileContents($path, "original")
    ->flatMap(fn($_) => readFileContents($path))
    ->map(fn($content) => strtoupper($content))
    ->flatMap(fn($upper) => writeFileContents($path, $upper))
    ->flatMap(fn($_) => readFileContents($path))
    ->unsafeRunSync();

// Result: "ORIGINAL"
```

See [examples/composition.php](examples/composition.php) and [doc/composition.md](doc/composition.md) for detailed patterns.

## Stream Operations

### through() - Pipe Operator

Apply transformation pipelines to streams:

```php
$uppercase = fn(Stream $s) => $s->map(fn($x) => strtoupper($x));

$result = Stream(...['hello', 'world'])
    ->through($uppercase)
    ->toArray();
// ['HELLO', 'WORLD']
```

### takeWhile() and dropWhile()

```php
// Take elements while condition is true
Stream(...[1, 2, 3, 4, 5, 1, 2])
    ->takeWhile(fn($x) => $x < 4)
    ->toArray();
// [1, 2, 3]

// Drop elements while condition is true
Stream(...[1, 2, 3, 4, 5])
    ->dropWhile(fn($x) => $x < 3)
    ->toArray();
// [3, 4, 5]
```

### chunk() - Batch Processing

```php
Stream(...[1, 2, 3, 4, 5, 6])
    ->chunk(2)
    ->toArray();
// [[1, 2], [3, 4], [5, 6]]
```

See [examples/stream-operations.php](examples/stream-operations.php) for 20 comprehensive examples.

## File I/O with Streams

### writeFile() - Stream Pipe for Writing

Write stream elements directly to files using the `writeFile()` pipe:

```php
use function Phunkie\Streams\IO\File\{writeFile, readLines};

// Write stream to file
Stream(...['line1', 'line2', 'line3'])
    ->through(writeFile(new Path('/tmp/output.txt')));

// With transformations
Stream(...[1, 2, 3, 4, 5])
    ->filter(fn($x) => $x % 2 === 0)
    ->map(fn($x) => "Even: $x")
    ->through(writeFile(new Path('/tmp/evens.txt')));

// Complex pipeline
$processData = fn(Stream $s) => $s
    ->dropWhile(fn($x) => $x < 5)
    ->takeWhile(fn($x) => $x <= 15)
    ->filter(fn($x) => $x % 2 === 0)
    ->map(fn($x) => "Value: $x");

Stream(...range(1, 20))
    ->through($processData)
    ->through(writeFile(new Path('/tmp/processed.txt')));
```

See [examples/file-pipes.php](examples/file-pipes.php) for 12 comprehensive file I/O examples.

## Network Operations

### HTTP Requests

Make HTTP requests as streams:

```php
use Phunkie\Streams\Network;

// HTTP GET
$data = Network::httpGet('https://api.example.com/data')
    ->compile->toArray();

// HTTP POST with JSON
Network::httpPost(
    'https://api.example.com/users',
    json_encode(['name' => 'Alice']),
    ['Content-Type: application/json']
)->compile->toArray();

// Stream processing
Network::httpGet('https://api.example.com/stream')
    ->map(fn($chunk) => json_decode($chunk, true))
    ->filter(fn($data) => $data !== null)
    ->compile->toArray();
```

### TCP Sockets

#### TCP Client

```php
use Phunkie\Streams\{Network, IO\Network\SocketAddress};

Network::client(new SocketAddress('localhost', 8080))
    ->map(fn($data) => processData($data))
    ->compile->toArray();
```

#### TCP Server

```php
Network::server(host: 'localhost', port: 8080)
    ->map(function($client) {
        $data = fread($client, 1024);
        fwrite($client, "Echo: $data");
        fclose($client);
        return "Handled client";
    })
    ->take(10)
    ->compile->drain;
```

#### Writing to Sockets

```php
Stream(...['message1', 'message2', 'message3'])
    ->through(Network::socketWrite(
        new SocketAddress('localhost', 8080)
    ));
```

See [examples/network.php](examples/network.php) for 15 comprehensive network examples.

## Concurrency & Parallel Processing

Phunkie Streams provides concurrent stream processing with automatic fallback to sequential execution:

### Parallel Map Operations

```php
use Phunkie\Streams\Type\Stream;

// Process elements in parallel (max 4 concurrent)
Stream(1, 2, 3, 4, 5, 6, 7, 8)
    ->parMap(4, fn($x) => expensiveComputation($x))
    ->compile()
    ->toArray();

// Parallel with error collection (doesn't fail fast)
Stream(1, 2, 3, 4, 5)
    ->parMapValidation(2, fn($x) => riskyOperation($x))
    ->compile()
    ->toArray();
// Returns: [Success(1), Failure($e), Success(3), ...]
```

### Parallel IO Effects

```php
use Phunkie\Streams\Network;
use Phunkie\Effect\IO\IO;

// Process IO effects in parallel
Stream("url1", "url2", "url3")
    ->parEvalMap(2, fn($url) => Network::httpGet($url))
    ->compile()
    ->drain
    ->unsafeRunSync();

// Deferred parallel execution
$io = Stream("url1", "url2", "url3")
    ->parTraverse(2, fn($url) => Network::httpGet($url));

$results = $io->unsafeRunSync(); // Stream of results
```

### Parallel Stream Merging

```php
// Merge multiple streams concurrently
$stream1 = Stream(1, 2, 3);
$stream2 = Stream(4, 5, 6);
$stream3 = Stream(7, 8, 9);

Stream::parMerge($stream1, $stream2, $stream3)
    ->compile()
    ->toArray();

// Concurrent flatMap
Stream("user1", "user2", "user3")
    ->parMergeMap(2, fn($user) =>
        Stream($user->getPosts())
    )
    ->compile()
    ->toArray(); // All posts from all users
```

### Memory Efficiency

All parallel operations are memory-optimized to process data incrementally:
- Auto-detects CPU cores when `maxConcurrent = 0`
- Processes elements in chunks without materializing entire streams
- Automatic fallback to sequential execution on concurrency failures
- Constant memory usage regardless of stream size

## Memory Optimization

Phunkie Streams is designed for **constant memory usage** regardless of data size. All operations use true streaming with lazy evaluation:

### File Operations

The `writeFile()` pipe function processes streams incrementally without loading everything into memory:

```php
use function Phunkie\Streams\IO\File\writeFile;

// Process a 10GB log file with constant ~4MB memory usage
Stream(new Path('huge-10gb-log.txt'))
    ->filter(fn($line) => str_contains($line, 'ERROR'))
    ->map(fn($line) => processLine($line))
    ->through(writeFile(new Path('errors.txt')));
```

### Parallel Operations

All parallel operations (`parMap`, `parEvalMap`, `parTraverse`, `parMerge`, `parMergeMap`) process data in chunks without materializing entire streams:

```php
// Process millions of records with bounded memory
Stream::fromLargeDataset()
    ->parMap(4, fn($record) => processRecord($record))
    ->chunk(1000)
    ->through(writeFile(new Path('output.txt')));
```

### Iterator Protocol

Operations use PHP's Iterator protocol for memory-efficient streaming:
- Elements are pulled on-demand, one at a time
- Transformations (map, filter) are applied per-element
- No intermediate arrays are created unnecessarily
- Memory usage stays constant regardless of input size

### Best Practices

1. **Avoid `toArray()` on large streams** - Use `compile->drain` for side effects
2. **Use `chunk()` for batching** - Process data in manageable batches
3. **Leverage `through()`** - Compose pipes for reusable transformations
4. **Use parallel operations** - Let the library handle concurrent processing efficiently

## Documentation

- [Resource Management Guide](doc/resource-management.md) - When to use bracket() vs __destruct()
- [Error Handling Guide](doc/error-handling.md) - Error recovery strategies
- [Composition Guide](doc/composition.md) - Monadic composition patterns
- [Full Documentation](./doc) - Complete documentation directory

## Examples

- [examples/bracket.php](examples/bracket.php) - Resource management (10 examples)
- [examples/error-handling.php](examples/error-handling.php) - Error handling (12 examples)
- [examples/composition.php](examples/composition.php) - Stream composition (12 examples)
- [examples/stream-operations.php](examples/stream-operations.php) - Stream operations (20 examples)
- [examples/file-pipes.php](examples/file-pipes.php) - File I/O with streams (12 examples)
- [examples/network.php](examples/network.php) - Network operations (15 examples)

## Contributing

We welcome contributions to Phunkie Streams! Please see our [Contributing Guide](CONTRIBUTING.md) for more information.

## License

Phunkie Streams is licensed under the LICENSE file included in the repository.
