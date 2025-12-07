# Working with Streams

This guide covers the practical aspects of working with Phunkie Streams, providing detailed examples of stream creation, transformation, combination, and consumption techniques.

> **Note**: This document describes both currently implemented operations and potential future operations. Some operations shown here (like `mapWithIndex`, `distinct`, `sorted`, `peek`, `recover`, statistical methods) are not yet implemented and are marked as examples of what could be added. For currently available operations, see the [API reference](getting-started.md) or the working examples in the [examples directory](../examples/).

## Creating Streams

Phunkie Streams offers various ways to create streams from different data sources.

### Creating from Values

The most straightforward way to create a stream is from individual values:

```php
<?php

// Simple values
$stream = Stream(1, 2, 3, 4, 5);

// Mixed types (though generally not recommended for type safety)
$mixedStream = Stream(1, "two", 3.0, [4], true);
```

### Creating from Arrays

You can easily convert PHP arrays to streams:

```php
<?php

// From a regular array
$array = [1, 2, 3, 4, 5];
$stream = Stream(...$array);

// From an associative array (keys will be lost)
$assocArray = ["a" => 1, "b" => 2, "c" => 3];
$stream = Stream(...array_values($assocArray));
```

### Creating from Generator Functions

Streams can be created from generator functions for efficient, lazy evaluation:

```php
<?php

// Define a generator function
function generateValues() {
    for ($i = 1; $i <= 5; $i++) {
        yield $i;
    }
}

// Create a stream from the generator
$generator = generateValues();
$stream = Stream(...$generator);
```

### Creating from Infinite Sources

Phunkie Streams provides functions for creating potentially infinite streams:

```php
<?php
// These functions are available in global namespace
// From a number range (potentially infinite)
$naturals = Stream(fromRange(1)); // All natural numbers starting from 1
$specificRange = Stream(fromRange(1, 100)); // Numbers from 1 to 100

// From an iteration function
$powers2 = Stream(iterate(1)(fn($x) => $x * 2)); // Powers of 2: 1, 2, 4, 8, 16, ...
$fibonacci = Stream(iterate(Pair(0, 1))(fn($p) => Pair($p->_2, $p->_1 + $p->_2)));
```

### Creating from File Resources

For working with files and other resources:

```php
<?php
use function Phunkie\Streams\IO\File\Path;

// Create a stream from a file (reads line by line)
$filePath = Path('path/to/file.txt');
$fileStream = Stream($filePath);

// With a specific buffer size (in bytes)
$largeFileStream = Stream(Path('path/to/file.txt'), 4096);
```

## Transforming Streams

Phunkie Streams provides a rich set of operations for transforming streams.

### Mapping Operations

Map operations transform each element in a stream:

```php
<?php

// Basic map: double each element
$doubled = Stream(1, 2, 3, 4, 5)
    ->map(fn($x) => $x * 2)
    ->toArray(); // [2, 4, 6, 8, 10]

// Map with index
$indexed = Stream("a", "b", "c")
    ->mapWithIndex(fn($x, $i) => "$i:$x")
    ->toArray(); // ["0:a", "1:b", "2:c"]

// FlatMap: map and flatten the result
$flattened = Stream(1, 2, 3)
    ->flatMap(fn($x) => [$x, $x * 10])
    ->toArray(); // [1, 10, 2, 20, 3, 30]
```

### Filtering Operations

Filter operations select elements based on predicates:

```php
<?php

// Basic filter: keep only even numbers
$evens = Stream(1, 2, 3, 4, 5, 6)
    ->filter(fn($x) => $x % 2 === 0)
    ->toArray(); // [2, 4, 6]

// Filter with negation
$odds = Stream(1, 2, 3, 4, 5, 6)
    ->filterNot(fn($x) => $x % 2 === 0)
    ->toArray(); // [1, 3, 5]

// Take while: keep elements until predicate fails
$underFour = Stream(1, 2, 3, 4, 5, 6)
    ->takeWhile(fn($x) => $x < 4)
    ->toArray(); // [1, 2, 3]

// Drop while: skip elements until predicate fails
$fourAndAbove = Stream(1, 2, 3, 4, 5, 6)
    ->dropWhile(fn($x) => $x < 4)
    ->toArray(); // [4, 5, 6]
```

### Limiting and Skipping

Control the number of elements in a stream:

```php
<?php
use function Phunkie\Streams\Infinite\fromRange;

// Take: keep only the first n elements
$first3 = Stream(1, 2, 3, 4, 5)
    ->take(3)
    ->compile()
    ->toArray(); // [1, 2, 3]

// Drop: skip the first n elements
$last2 = Stream(1, 2, 3, 4, 5)
    ->drop(3)
    ->compile()
    ->toArray(); // [4, 5]

// Especially useful for infinite streams
$first10Naturals = Stream(fromRange(1))
    ->take(10)
    ->compile()
    ->toArray(); // [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
```

### Other Transformations

Additional useful transformations:

```php
<?php

// Distinct: keep only unique elements
$unique = Stream(1, 2, 2, 3, 3, 3, 4)
    ->distinct()
    ->toArray(); // [1, 2, 3, 4]

// Sort: sort elements in natural order
$sorted = Stream(3, 1, 4, 2, 5)
    ->sorted()
    ->toArray(); // [1, 2, 3, 4, 5]

// Custom sort
$customSorted = Stream(3, 1, 4, 2, 5)
    ->sortBy(fn($a, $b) => $b <=> $a) // Descending order
    ->toArray(); // [5, 4, 3, 2, 1]
```

## Combining Streams

Phunkie Streams provides operations for combining multiple streams.

### Concatenation

Join streams sequentially:

```php
<?php

// Concatenate two streams
$combined = Stream(1, 2, 3)
    ->concat(Stream(4, 5, 6))
    ->compile()
    ->toArray(); // [1, 2, 3, 4, 5, 6]

// Concatenate multiple streams
$multiCombined = Stream(1, 2)
    ->concat(Stream(3, 4))
    ->concat(Stream(5, 6))
    ->compile()
    ->toArray(); // [1, 2, 3, 4, 5, 6]
```

### Zipping

Combine streams element-wise:

```php
<?php

// Zip two streams into pairs
$zipped = Stream(1, 2, 3)
    ->zip(Stream("a", "b", "c"))
    ->toArray(); // [Pair(1, "a"), Pair(2, "b"), Pair(3, "c")]

// Zip with a function
$summed = Stream(1, 2, 3)
    ->zipWith(Stream(4, 5, 6))(fn($a, $b) => $a + $b)
    ->toArray(); // [5, 7, 9]

// Note: zipping stops when the shortest stream is exhausted
$partial = Stream(1, 2, 3, 4)
    ->zip(Stream("a", "b"))
    ->toArray(); // [Pair(1, "a"), Pair(2, "b")]
```

### Interleaving

Alternate elements from multiple streams:

```php
<?php

// Interleave two streams
$interleaved = Stream(1, 3, 5)
    ->interleave(Stream(2, 4, 6))
    ->compile()
    ->toArray(); // [1, 2, 3, 4, 5, 6]

// Interleave multiple streams
$multiInterleaved = Stream(1, 4, 7)
    ->interleave(Stream(2, 5, 8), Stream(3, 6, 9))
    ->compile()
    ->toArray(); // [1, 2, 3, 4, 5, 6, 7, 8, 9]
```

## Consuming Streams

Phunkie Streams provides various ways to consume streams and produce final results.

### Conversion to Collections

Convert streams to other collection types:

```php
<?php

$stream = Stream(1, 2, 3, 4, 5);

// Convert to array
$array = $stream->toArray(); // [1, 2, 3, 4, 5]

// Convert to List
$list = $stream->toList(); // List(1, 2, 3, 4, 5)
```

### Iteration

Process each element:

```php
<?php

// Process each element with foreach
$stream = Stream(1, 2, 3, 4, 5);
$stream->foreach(function($element) {
    echo "Processing: $element\n";
});

// With index
$stream->foreachWithIndex(function($element, $index) {
    echo "Element at position $index: $element\n";
});
```

### Reduction

Reduce a stream to a single value:

```php
<?php

$stream = Stream(1, 2, 3, 4, 5);

// Sum all elements
$sum = $stream->fold(0)(fn($acc, $x) => $acc + $x); // 15

// Find maximum value
$max = $stream->fold(PHP_INT_MIN)(fn($acc, $x) => max($acc, $x)); // 5

// Concatenate strings
$joined = Stream("a", "b", "c")
    ->fold("")(fn($acc, $x) => $acc . $x); // "abc"
```

### Finding Elements

Search for specific elements:

```php
<?php

$stream = Stream(1, 2, 3, 4, 5);

// Find the first element matching a predicate
$firstEven = $stream->find(fn($x) => $x % 2 === 0); // Option(2)

// Check if any element satisfies a predicate
$hasEven = $stream->exists(fn($x) => $x % 2 === 0); // true

// Check if all elements satisfy a predicate
$allPositive = $stream->forall(fn($x) => $x > 0); // true
$allEven = $stream->forall(fn($x) => $x % 2 === 0); // false
```

### Statistical Operations

Compute statistics on numeric streams:

```php
<?php

$numbers = Stream(1, 2, 3, 4, 5);

// Count elements
$count = $numbers->count(); // 5

// Sum
$sum = $numbers->sum(); // 15

// Average
$average = $numbers->average(); // 3.0

// Minimum and maximum
$min = $numbers->min(); // 1
$max = $numbers->max(); // 5
```

### Working with IO Streams

Handling streams that interact with external resources:

```php
<?php
use Phunkie\Streams\IO\File\Path;

// Process a file line by line
$filePath = new Path('path/to/file.txt');
$lines = Stream($filePath)
    ->map(fn($line) => strtoupper($line))
    ->filter(fn($line) => !empty(trim($line)))
    ->compile()
    ->toArray();

// Using file I/O functions for simple operations
use function Phunkie\Streams\IO\File\readFileContents;

$content = readFileContents($filePath)
    ->map(fn($text) => strtoupper($text))
    ->unsafeRunSync();
```

## Handling Errors

Phunkie Streams provides methods for handling errors:

```php
<?php

// Recover from errors
$safeStream = Stream(1, 2, "3", 4, "five")
    ->map(fn($x) => $x * 2) // This will fail for non-numeric values
    ->recover(fn($error) => 0); // Replace errors with 0

// Or handle specific exceptions
$safeStream = Stream(1, 2, "3", 4, "five")
    ->map(function($x) {
        if (!is_numeric($x)) {
            throw new \InvalidArgumentException("Not a number: $x");
        }
        return $x * 2;
    })
    ->recoverWith(function($error) {
        if ($error instanceof \InvalidArgumentException) {
            return 0;
        }
        throw $error; // Rethrow other exceptions
    });
```

## Best Practices

### Performance Considerations

- For large streams, prefer lazy operations until a terminal operation is needed
- Set an appropriate buffer size for IO operations
- Use `take` early in the pipeline when working with infinite streams
- Avoid constructing unnecessary intermediate collections

### Debugging Streams

```php
<?php

// Inspect elements during processing
$result = Stream(1, 2, 3, 4, 5)
    ->map(fn($x) => $x * 2)
    ->peek(fn($x) => echo "After mapping: $x\n")
    ->filter(fn($x) => $x > 5)
    ->peek(fn($x) => echo "After filtering: $x\n")
    ->toArray();
```

### Composing Stream Operations

For reusable stream transformations:

```php
<?php

// Define reusable transformations
function doubleAll($stream) {
    return $stream->map(fn($x) => $x * 2);
}

function keepEvens($stream) {
    return $stream->filter(fn($x) => $x % 2 === 0);
}

// Compose transformations
$result = keepEvens(doubleAll(Stream(1, 2, 3, 4, 5)))->toArray(); // [4, 8]
```

## Next Steps

- [Pure Streams](pure-streams.md): Learn more about working with pure functional streams
- [Infinite Streams](infinite-streams.md): Explore techniques for handling potentially unbounded data
- [Resource Streams](resource-streams.md): Dive deeper into working with external resources 