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

See [examples/stream-operations.php](examples/stream-operations.php) for 15 comprehensive examples.

## Documentation

- [Error Handling Guide](doc/error-handling.md) - Error recovery strategies
- [Composition Guide](doc/composition.md) - Monadic composition patterns
- [Full Documentation](./doc) - Complete documentation directory

## Examples

- [examples/bracket.php](examples/bracket.php) - Resource management (10 examples)
- [examples/error-handling.php](examples/error-handling.php) - Error handling (12 examples)
- [examples/composition.php](examples/composition.php) - Stream composition (12 examples)
- [examples/stream-operations.php](examples/stream-operations.php) - Stream operations (15 examples)

## Contributing

We welcome contributions to Phunkie Streams! Please see our [Contributing Guide](CONTRIBUTING.md) for more information.

## License

Phunkie Streams is licensed under the LICENSE file included in the repository.
