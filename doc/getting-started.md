# Getting Started with Phunkie Streams

This guide will help you get up and running with Phunkie Streams in your PHP projects.

## Installation

Phunkie Streams can be installed via Composer, the PHP package manager.

### Requirements

- PHP 8.1 or higher
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

use function Phunkie\Streams\Stream;

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
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Infinite\fromRange;
use function Phunkie\Streams\Infinite\iterate;
use function Phunkie\Streams\IO\fromResource;

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
$fileStream = Stream(fromResource($filePath));
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

## Configuration

Phunkie Streams provides configuration options to tailor its behavior to your needs.

### Setting Stream Buffer Size

You can configure the buffer size for stream operations:

```php
<?php
// Create a stream with custom buffer size (in bytes)
$stream = Stream(1, 2, 3, 4, 5)->setBytes(1024);

// For file operations, specify buffer size during creation
use function Phunkie\Streams\IO\fromResource;
$filePath = new Path('path/to/large/file.txt');
$fileStream = Stream(fromResource($filePath, 4096)); // 4KB buffer
```

### Resource Management

Configure how resources are managed:

```php
<?php
use function Phunkie\Streams\IO\fromResource;
use Phunkie\Streams\Type\Scope;

// Create a custom scope
$scope = new Scope();

// Set the scope for a stream
$filePath = new Path('path/to/file.txt');
$fileStream = Stream(fromResource($filePath));
$fileStream->setScope($scope);

// Work with the stream...

// The scope will ensure resources are properly closed,
// even if exceptions occur during processing
```

### Performance Tips

To optimize performance with Phunkie Streams:

1. Choose appropriate buffer sizes for your data
2. Use lazy operations when processing large datasets
3. Limit the number of elements from infinite streams as early as possible
4. Release resources explicitly when they're no longer needed

## Next Steps

Now that you've learned the basics of Phunkie Streams, you can explore more advanced topics:

- [Core Concepts](core-concepts.md): Understand the fundamental abstractions
- [Working with Streams](working-with-streams.md): Learn more about stream operations
- [Pure Streams](pure-streams.md): Dive deeper into pure functional streams
- [Infinite Streams](infinite-streams.md): Master working with unbounded data 