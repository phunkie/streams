# Pure Streams

Pure streams are a fundamental concept in Phunkie Streams, representing sequences of values that can be processed without side effects.

## What are Pure Streams?

In functional programming, "purity" refers to computations that:

- Don't modify state outside their scope
- Don't interact with the external world (files, network, etc.)
- Always produce the same output for a given input
- Have no observable side effects

Pure streams in Phunkie embody these principles, providing a safe, predictable way to process data within your application.

### Pure Streams vs. IO Streams

Phunkie Streams distinguishes between two primary effect types:

| Characteristic | Pure Streams | IO Streams |
|----------------|--------------|------------|
| Data source | In-memory values | External resources (files, network) |
| Side effects | None | May have side effects |
| Determinism | Same input always produces same output | May vary based on external factors |
| Terminal operations | Can use `toArray()`, `toList()` directly | Need compilation with `compile()` first |
| Error handling | Generally more predictable | Need more robust error handling |

This distinction helps maintain a clear separation between pure data transformations and effectful operations that interact with the outside world.

## Creating Pure Streams

### From Values

The most direct way to create a pure stream is from individual values:

```php
<?php

// Stream of integers
$stream = Stream(1, 2, 3, 4, 5);

// Stream of strings
$names = Stream("Alice", "Bob", "Charlie");

// Mixed types (though generally not recommended for type safety)
$mixed = Stream(1, "two", 3.0, true);
```

### From Arrays

Convert PHP arrays to pure streams:

```php
<?php

// From a numeric array
$array = [1, 2, 3, 4, 5];
$stream = Stream(...$array);

// From an associative array (keys will be lost in the stream)
$assocArray = ["a" => 1, "b" => 2, "c" => 3];
$stream = Stream(...array_values($assocArray));
```

### From Generators

For more memory-efficient stream creation, especially with large datasets:

```php
<?php

function generateSequence($n) {
    for ($i = 1; $i <= $n; $i++) {
        yield $i;
    }
}

// Create a stream from a generator
$generator = generateSequence(1000); // Generates numbers 1 to 1000
$stream = Stream(...$generator);
```

### Empty Streams

Create empty streams when needed:

```php
<?php

// An empty stream
$empty = Stream();
```

## Operations on Pure Streams

Pure streams support a rich set of operations that maintain purity.

### Transformations

Map, filter, and other transformations create new streams without modifying the original:

```php
<?php

$stream = Stream(1, 2, 3, 4, 5);

// Mapping: transform each element
$doubled = $stream->map(fn($x) => $x * 2); // Stream(2, 4, 6, 8, 10)

// Filtering: select elements based on a condition
$evens = $stream->filter(fn($x) => $x % 2 === 0); // Stream(2, 4)

// Both operations preserve the original stream
$originalArray = $stream->toArray(); // Still [1, 2, 3, 4, 5]
```

### Composition

Operations can be composed to build complex transformations:

```php
<?php

$result = Stream(1, 2, 3, 4, 5)
    ->map(fn($x) => $x * 2) // Stream(2, 4, 6, 8, 10)
    ->filter(fn($x) => $x > 5) // Stream(6, 8, 10)
    ->map(fn($x) => "Number: $x")
    ->toArray(); // ["Number: 6", "Number: 8", "Number: 10"]
```

### Higher-Order Functions

Pure streams work well with higher-order functions:

```php
<?php

// Define reusable transformations
$double = fn($x) => $x * 2;
$isEven = fn($x) => $x % 2 === 0;
$addPrefix = fn($x) => "Value: $x";

// Apply them to streams
$result = Stream(1, 2, 3, 4, 5)
    ->filter($isEven)    // Stream(2, 4) - only 2 and 4 are even
    ->map($double)       // Stream(4, 8) - double the even numbers
    ->map($addPrefix)    // Stream("Value: 4", "Value: 8")
    ->toArray();         // ["Value: 4", "Value: 8"]
```

## Terminal Operations

Pure streams offer several terminal operations that consume the stream and produce a final result.

### Conversion to Collections

```php
<?php

$stream = Stream(1, 2, 3, 4, 5);

// Convert to a PHP array
$array = $stream->toArray(); // [1, 2, 3, 4, 5]

// Convert to a Phunkie List
$list = $stream->toList(); // List(1, 2, 3, 4, 5)
```

### Reduction

Aggregate stream elements into a single result:

```php
<?php

$stream = Stream(1, 2, 3, 4, 5);

// Sum all elements
$sum = $stream->fold(0)(fn($acc, $x) => $acc + $x); // 15

// Find the maximum
$max = $stream->fold(PHP_INT_MIN)(fn($acc, $x) => max($acc, $x)); // 5

// Join strings
$joined = Stream("Hello", "functional", "world")
    ->fold("")(fn($acc, $word) => $acc . ($acc ? " " : "") . $word); // "Hello functional world"
```

### Element Access

Access specific elements from the stream:

```php
<?php

$stream = Stream(1, 2, 3, 4, 5);

// Get the first element
$first = $stream->head(); // 1

// Get all but the first element
$rest = $stream->tail(); // Stream(2, 3, 4, 5)

// Check if the stream is empty
$isEmpty = $stream->isEmpty(); // false

// Find an element matching a predicate
$found = $stream->find(fn($x) => $x > 3); // Option(4)
```

## Pure Stream Examples

### Data Processing Pipeline

```php
<?php

// Process a list of user data
$users = [
    ["name" => "Alice", "age" => 30, "active" => true],
    ["name" => "Bob", "age" => 25, "active" => false],
    ["name" => "Charlie", "age" => 35, "active" => true],
    ["name" => "David", "age" => 40, "active" => true],
];

$result = Stream(...$users)
    ->filter(fn($user) => $user["active"]) // Only active users
    ->filter(fn($user) => $user["age"] >= 30) // Age 30 or older
    ->map(fn($user) => $user["name"]) // Extract names
    ->toArray(); // ["Alice", "Charlie", "David"]
```

### Mathematical Computations

```php
<?php

// Generate a sequence of squares
$squares = Stream(1, 2, 3, 4, 5, 6, 7, 8, 9, 10)
    ->map(fn($x) => $x * $x)
    ->toArray(); // [1, 4, 9, 16, 25, 36, 49, 64, 81, 100]

// Calculate factorial using fold
$n = 5;
$factorial = Stream(...range(1, $n))
    ->fold(1)(fn($acc, $x) => $acc * $x); // 120 (5!)
```

### Text Processing

```php
<?php

// Split text into words, count their occurrences
$text = "The quick brown fox jumps over the lazy dog";
$words = explode(" ", strtolower($text));

$wordCounts = Stream(...$words)
    ->fold([])(function($counts, $word) {
        $counts[$word] = ($counts[$word] ?? 0) + 1;
        return $counts;
    });
// ["the" => 2, "quick" => 1, "brown" => 1, "fox" => 1, ...etc]
```

## Advantages of Pure Streams

### Testability

Pure operations are easier to test because they don't depend on external state:

```php
<?php

// A pure function that processes a stream
function doubleEvens($stream) {
    return $stream
        ->filter(fn($x) => $x % 2 === 0)
        ->map(fn($x) => $x * 2);
}

// Easy to test with predictable inputs and outputs
$input = Stream(1, 2, 3, 4, 5);
$expected = [4, 8]; // Doubled even numbers
$actual = doubleEvens($input)->toArray();
assert($expected === $actual);
```

### Reasoning

Pure code is easier to reason about because it can't cause unexpected side effects:

- No mutation of external state
- No hidden dependencies
- Predictable execution

### Composition

Pure operations compose well, allowing complex transformations to be built from simple building blocks:

```php
<?php

// Define reusable stream transformations
function onlyPositive($stream) {
    return $stream->filter(fn($x) => $x > 0);
}

function doubleAll($stream) {
    return $stream->map(fn($x) => $x * 2);
}

function sumElements($stream) {
    return $stream->fold(0)(fn($acc, $x) => $acc + $x);
}

// Combine them to form a complex operation
$input = Stream(-2, -1, 0, 1, 2, 3);
$result = sumElements(doubleAll(onlyPositive($input))); // (1+2+3)*2 = 12
```

## Best Practices for Pure Streams

1. **Keep operations pure**: Avoid side effects within stream transformations
2. **Use appropriate terminal operations**: Choose the right way to consume your stream
3. **Compose operations**: Build complex processing pipelines from simple operations
4. **Leverage laziness**: Pure streams evaluate lazily, so you can define transformations without immediate execution
5. **Prefer immutability**: Don't modify data structures, create new ones instead

## Next Steps

- [Infinite Streams](infinite-streams.md): Learn how to use potentially infinite sequences
- [Working with Streams](working-with-streams.md): Explore more operations available for all stream types
- [Resource Streams](resource-streams.md): Understand how to safely work with external resources 