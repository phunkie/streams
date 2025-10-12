# Core Concepts in Phunkie Streams

This document explains the fundamental abstractions that power Phunkie Streams. Understanding these concepts will help you better utilize the library and comprehend its internal architecture.

## Stream Abstraction

At the heart of Phunkie Streams is the `Stream` abstraction, which represents a sequence of elements that can be processed functionally.

### Stream Properties

A Stream in Phunkie has several key properties:

- **Laziness**: Computations are only performed when results are needed
- **Immutability**: Operations create new streams rather than modifying existing ones
- **Composability**: Operations can be chained to form complex transformations
- **Effect typing**: Streams are typed with the kind of effects they perform (Pure, IO)

### Stream Internal Structure

Internally, a Stream is a thin wrapper around a `Pull` object, which handles the actual evaluation strategy. The Stream provides a higher-level, user-friendly API over the more primitive Pull operations.

```php
<?php
use function Phunkie\Streams\Stream;

// Creating a stream of values
$stream = Stream(1, 2, 3, 4);

// The stream is not evaluated until a terminal operation is called
$result = $stream
    ->map(fn($x) => $x * 2)
    ->filter(fn($x) => $x > 5)
    ->toArray(); // Terminal operation that evaluates the stream
```

### Stream Lifecycle

1. **Creation**: A Stream is created from a source (values, arrays, resources)
2. **Transformation**: Operations like `map`, `filter`, and `concat` build a pipeline
3. **Compilation**: The `compile` method prepares the stream for evaluation
4. **Consumption**: Terminal operations like `toArray` or `fold` consume the stream

## Pull Mechanism

The `Pull` abstraction is the lower-level engine that powers Stream operations. It represents a primitive that knows how to produce the next element in a stream.

### Types of Pull

Phunkie Streams implements several Pull types:

- **ValuesPull**: For handling pure finite streams of in-memory values
- **InfinitePull**: For working with potentially infinite sequences
- **ResourcePull**: For interfacing with raw PHP stream resources (files)
- **ResourceObjectPull**: For Resource interface objects (HttpRequest, SocketRead, etc.)
- **ResourcePullConcat**: For concatenating resource-based streams

### Pull Operations

Pull operations are more primitive than Stream operations:

```php
<?php
// This code illustrates the internal structure and is not meant to be used directly

use Phunkie\Streams\Pull\ValuesPull;

// Create a Pull abstraction
$pull = new ValuesPull(1, 2, 3, 4);

// Pulling values one by one
while ($pull->valid()) {
    $element = $pull->current();
    $pull->next();
    // Process $element...
}
```

### Transformation Pipeline

When you chain operations on a Stream, you're building a pipeline of Pull operations that will be executed when a terminal operation is called:

1. Each operation creates a new Stream with a modified Pull
2. The Pull objects form a chain of steps to execute
3. When a terminal operation is called, the chain is evaluated from start to finish

## Scope and Transformation Management

Phunkie Streams uses an internal abstraction called `Scope` to manage transformations across stream operations. Scope is used internally by the library and is not typically manipulated directly by users.

### What Scope Does

Scope serves different purposes depending on the type of stream:

**For ValuesPull (pure streams):**
- Stores a chain of `Transformation` objects
- Transformations are applied during compilation
- Supports complex operations like `filter()`, `takeWhile()`, `dropWhile()`, `chunk()`

**For ResourceObjectPull (network/resource streams):**
- Stores `map()` functions to transform stream elements
- Stores `filter()` predicates to filter elements
- Transformations are applied during value extraction

### How It Works Internally

When you chain operations on a stream, Scope accumulates the transformations:

```php
<?php
use function Phunkie\Streams\Stream;

// Each operation adds to the Scope
$stream = Stream(1, 2, 3, 4, 5)
    ->filter(fn($x) => $x % 2 === 0)  // Adds filter to Scope
    ->map(fn($x) => $x * 2)            // Adds map to Scope
    ->takeWhile(fn($x) => $x < 10);    // Adds takeWhile to Scope

// Transformations are applied when the stream is consumed
$result = $stream->toArray(); // [4, 8]
```

### Why Scope Matters

Scope enables:

1. **Lazy evaluation** - Transformations are accumulated, not immediately applied
2. **Optimization** - Multiple transformations can be optimized together
3. **Resource safety** - Transformations are applied while resource is managed
4. **Effect handling** - Separates pure transformations from effectful operations

Users don't need to interact with Scope directly - it works behind the scenes to make stream operations efficient and safe.

## Effect Types

Phunkie Streams distinguishes between different types of effects that streams can perform.

### Pure Streams

Pure streams represent computations that don't interact with the outside world:

```php
<?php
// This is a pure stream - it only processes in-memory values
$pureStream = Stream(1, 2, 3, 4)
    ->map(fn($x) => $x * 2)
    ->filter(fn($x) => $x > 5);

// Pure streams can be converted to collections directly
$result = $pureStream->toArray(); // [6, 8]
```

Pure streams:
- Don't access files, databases, or network resources
- Don't have side effects like printing to the console
- Are deterministic (same input, same output)
- Can be evaluated multiple times with the same result

### IO Streams

IO streams represent computations that interact with the outside world:

```php
<?php
use function Phunkie\Streams\Stream;
use Phunkie\Streams\{Network, IO\File\Path};

// File I/O stream
$fileStream = Stream(new Path('data.txt'))
    ->map(fn($line) => strtoupper($line));

// IO streams need to be compiled before consumption
$result = $fileStream->compile()->toArray();

// HTTP request stream
$httpStream = Network::httpGet('https://api.example.com/data')
    ->map(fn($chunk) => json_decode($chunk, true))
    ->filter(fn($data) => $data !== null);

$data = $httpStream->compile->toArray();
```

IO streams:
- Access external resources like files, databases, or network
- May have side effects
- Need proper resource management (handled automatically)
- Require explicit compilation before consumption

### Why Effect Typing Matters

Distinguishing between pure and IO streams allows:

1. **Safety**: Different operations are available based on the effect type
2. **Reasoning**: Pure code can be reasoned about more easily
3. **Optimization**: Pure streams can be optimized differently than IO streams
4. **Composition**: Effects can be composed and managed in a structured way

## Putting It All Together

These core concepts work together to form the foundation of Phunkie Streams:

1. **Streams** provide the user-facing API
2. **Pull** handles the evaluation strategy
3. **Scope** manages stream transaformations
4. **Effect typing** ensures safe handling of different computation types

Understanding these abstractions will help you use Phunkie Streams more effectively and build robust stream processing pipelines.

## Next Steps

Now that you understand the core concepts of Phunkie Streams, you can explore:

- [Working with Streams](working-with-streams.md): Learn more advanced stream operations
- [Pure Streams](pure-streams.md): Dive deeper into pure functional streams
- [Resource Streams](resource-streams.md): Learn more about working with external resources 