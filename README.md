# Phunkie Streams #

Phunkie Streams is a PHP functional library for working with streams inspired by functional streaming libraries like fs2 (Scala). It allows you to process data in a declarative, composable way.

## Installation

```
composer require phunkie/streams
```

## Features

- **Pure streams**: Finite sequences of values that can be transformed and combined
- **Infinite streams**: Unbounded sequences that can be processed lazily
- **Effectful operations**: Operations that interact with the outside world (I/O, etc.)
- **Resource management**: Safe handling of resources through proper acquisition and release

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

## Documentation

For detailed documentation, visit the [docs](./doc) directory.

## Contributing

We welcome contributions to Phunkie Streams! Please see our [Contributing Guide](CONTRIBUTING.md) for more information.

## License

Phunkie Streams is licensed under the LICENSE file included in the repository.
