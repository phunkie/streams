# Infinite Streams

Infinite streams are a powerful feature of Phunkie Streams that allow you to work with potentially unbounded sequences of data in a safe, memory-efficient way.

## What are Infinite Streams?

Infinite streams represent sequences of values that conceptually have no end. Unlike traditional collections that must be fully materialized in memory, infinite streams:

- Can represent unbounded sequences like all natural numbers
- Are lazily evaluated, computing values only when needed
- Allow processing of theoretically infinite data with finite memory
- Must be handled with appropriate operations to avoid endless computation

## Creating Infinite Streams

Phunkie Streams provides several ways to create infinite streams.

### Using Iteration Functions

The primary way to create infinite streams is through iteration functions:

```php
<?php
use function Phunkie\Streams\Infinite\iterate;

// Natural numbers: 1, 2, 3, 4, ...
$naturals = Stream(iterate(1)(fn($x) => $x + 1));

// All integers starting from -100
$negativeAndUp = Stream(iterate(-100)(fn($x) => $x + 1));

// Counting by 5s: 5, 10, 15, ...
$fiveMultiples = Stream(iterate(5)(fn($x) => $x + 5));

// Powers of 2: 1, 2, 4, 8, 16, ...
$powersOf2 = Stream(iterate(1)(fn($x) => $x * 2));

// Countdown: 10, 9, 8, 7, ...
$countdown = Stream(iterate(10)(fn($x) => $x - 1));

// Fibonacci sequence using pairs
$fibonacci = Stream(iterate(Pair(0, 1))(fn($p) => Pair($p->_2, $p->_1 + $p->_2)));
```

### Using Unfold

The `unfold` function is another powerful way to create infinite streams:

```php
<?php
use function Phunkie\Streams\Infinite\unfold;

// Natural numbers using unfold
$naturals = Stream(unfold(1)(
    fn($x) => Pair($x, $x + 1)
));

// Powers of 2 using unfold
$powersOf2 = Stream(unfold(1)(
    fn($x) => Pair($x, $x * 2)
));

// Fibonacci using unfold
$fibonacci = Stream(unfold(Pair(0, 1))(
    fn($p) => Pair($p->_1, Pair($p->_2, $p->_1 + $p->_2))
));
```

### Repeating Elements

Create streams by repeating elements or sequences:

```php
<?php

// Repeat a single value infinitely
$allOnes = Stream(1)->repeat();

// Repeat a sequence infinitely: 1, 2, 3, 1, 2, 3, ...
$repeatingSequence = Stream(1, 2, 3)->repeat();
```

### Contrasting with Finite Ranges

For comparison, here's how you would create finite ranges:

```php
<?php
use function Phunkie\Streams\Infinite\fromRange;

// Finite range from 1 to 10
$finiteRange = Stream(fromRange(1, 10));

// Finite range from 1 to 100
$largerRange = Stream(fromRange(1, 100));

// Very large but still finite range
$veryLargeRange = Stream(fromRange(1, 1000000));
```

## Processing Infinite Streams

Processing infinite streams requires techniques to limit the computation to a finite subset of elements.

### Limiting with take()

Use `take()` to limit an infinite stream to a specific number of elements:

```php
<?php
use function Phunkie\Streams\Infinite\iterate;

// Get the first 10 natural numbers
$firstTen = Stream(iterate(1)(fn($x) => $x + 1))
    ->take(10)
    ->compile()
    ->toArray(); // [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]

// Get the first 5 even numbers
$firstFiveEvens = Stream(iterate(1)(fn($x) => $x + 1))
    ->filter(fn($x) => $x % 2 === 0)
    ->take(5)
    ->compile()
    ->toArray(); // [2, 4, 6, 8, 10]
```

### Limiting with takeWhile()

Use `takeWhile()` to take elements while a condition is true:

```php
<?php
use function Phunkie\Streams\Infinite\iterate;

// Take numbers until we reach 10
$underTen = Stream(iterate(1)(fn($x) => $x + 1))
    ->takeWhile(fn($x) => $x < 10)
    ->compile()
    ->toArray(); // [1, 2, 3, 4, 5, 6, 7, 8, 9]

// Take prime numbers under 20
$primesUnder20 = Stream(iterate(2)(fn($x) => $x + 1))
    ->filter(function($n) {
        for ($i = 2; $i <= sqrt($n); $i++) {
            if ($n % $i === 0) return false;
        }
        return true;
    })
    ->takeWhile(fn($x) => $x < 20)
    ->compile()
    ->toArray(); // [2, 3, 5, 7, 11, 13, 17, 19]
```

### Important: Use Compilation

When working with infinite streams, always use `compile()` before terminal operations:

```php
<?php
use function Phunkie\Streams\Infinite\iterate;

// Correct way to process infinite streams
$result = Stream(iterate(1)(fn($x) => $x + 1))
    ->take(5)
    ->compile() // Compilation is necessary
    ->toArray();

// This would cause an infinite loop (or run until memory is exhausted)
// DON'T DO THIS with infinite streams:
// $result = Stream(iterate(1)(fn($x) => $x + 1))->toArray();
```

## Transforming Infinite Streams

You can apply transformations to infinite streams just like with finite streams:

```php
<?php
use function Phunkie\Streams\Infinite\iterate;

// Find squares of the first 10 natural numbers
$squares = Stream(iterate(1)(fn($x) => $x + 1))
    ->map(fn($x) => $x * $x)
    ->take(10)
    ->compile()
    ->toArray(); // [1, 4, 9, 16, 25, 36, 49, 64, 81, 100]

// Find the first 5 even numbers and triple them
$tripledEvens = Stream(iterate(1)(fn($x) => $x + 1))
    ->filter(fn($x) => $x % 2 === 0)
    ->map(fn($x) => $x * 3)
    ->take(5)
    ->compile()
    ->toArray(); // [6, 12, 18, 24, 30]
```

## Common Patterns and Use Cases

### Stream Fusion

Combine multiple operations efficiently:

```php
<?php
use function Phunkie\Streams\Infinite\iterate;

// Find the sum of squares of the first 100 even numbers
$sumOfSquares = Stream(iterate(1)(fn($x) => $x + 1))
    ->filter(fn($x) => $x % 2 === 0) // Even numbers
    ->take(100) // First 100 even numbers
    ->map(fn($x) => $x * $x) // Square them
    ->compile()
    ->fold(0)(fn($acc, $x) => $acc + $x); // Sum them
```

### Zipping Infinite Streams

Combine multiple infinite streams:

```php
<?php
use function Phunkie\Streams\Infinite\iterate;

// Create pairs of (number, square)
$numbersAndSquares = Stream(iterate(1)(fn($x) => $x + 1))
    ->zip(Stream(iterate(1)(fn($x) => $x + 1))->map(fn($x) => $x * $x))
    ->take(10)
    ->compile()
    ->toArray();
// [Pair(1, 1), Pair(2, 4), Pair(3, 9), ..., Pair(10, 100)]
```

### Infinite Stream Interleaving

Alternate between multiple streams:

```php
<?php
use function Phunkie\Streams\Infinite\iterate;

// Interleave natural numbers with their negatives: 1, -1, 2, -2, 3, -3, ...
$interleaved = Stream(iterate(1)(fn($x) => $x + 1))
    ->interleave(Stream(iterate(1)(fn($x) => $x + 1))->map(fn($x) => -$x))
    ->take(10)
    ->compile()
    ->toArray(); // [1, -1, 2, -2, 3, -3, 4, -4, 5, -5]
```

## Mathematical Applications

Infinite streams naturally model many mathematical concepts:

### Arithmetic Sequences

```php
<?php
use function Phunkie\Streams\Infinite\iterate;

// Arithmetic sequence with first term a=1 and common difference d=3
// 1, 4, 7, 10, ...
$arithmeticSequence = Stream(iterate(1)(fn($x) => $x + 3))
    ->take(10)
    ->compile()
    ->toArray(); // [1, 4, 7, 10, 13, 16, 19, 22, 25, 28]
```

### Geometric Sequences

```php
<?php
use function Phunkie\Streams\Infinite\iterate;

// Geometric sequence with first term a=1 and common ratio r=2
// 1, 2, 4, 8, 16, ...
$geometricSequence = Stream(iterate(1)(fn($x) => $x * 2))
    ->take(10)
    ->compile()
    ->toArray(); // [1, 2, 4, 8, 16, 32, 64, 128, 256, 512]
```

### Prime Number Generator

```php
<?php
use function Phunkie\Streams\Infinite\iterate;

// Generate prime numbers
function isPrime($n) {
    if ($n < 2) return false;
    for ($i = 2; $i <= sqrt($n); $i++) {
        if ($n % $i === 0) return false;
    }
    return true;
}

$primes = Stream(iterate(2)(fn($x) => $x + 1))
    ->filter('isPrime')
    ->take(10)
    ->compile()
    ->toArray(); // [2, 3, 5, 7, 11, 13, 17, 19, 23, 29]
```

## Avoiding Common Pitfalls

### Infinite Loop Prevention

Always limit infinite streams before terminal operations:

```php
<?php
use function Phunkie\Streams\Infinite\iterate;

// GOOD: Limit then consume
$goodExample = Stream(iterate(1)(fn($x) => $x + 1))
    ->take(10)
    ->compile()
    ->toArray();

// BAD: This would run forever or until memory is exhausted
// $badExample = Stream(iterate(1)(fn($x) => $x + 1))->toArray();
```

### Order of Operations Matters

Pay attention to the order of stream operations:

```php
<?php
use function Phunkie\Streams\Infinite\iterate;

// EFFICIENT: filter, then take
// Only computes enough elements to find 5 even numbers
$efficient = Stream(iterate(1)(fn($x) => $x + 1))
    ->filter(fn($x) => $x % 2 === 0)
    ->take(5)
    ->compile()
    ->toArray(); // [2, 4, 6, 8, 10]

// INEFFICIENT: take, then filter
// Computes 100 elements, then filters them
// (This still works but is less efficient)
$lessEfficient = Stream(iterate(1)(fn($x) => $x + 1))
    ->take(100)
    ->filter(fn($x) => $x % 2 === 0)
    ->compile()
    ->toArray(); // [2, 4, 6, ..., 100]
```

### Memory Efficiency

Process large or infinite streams in chunks if needed:

```php
<?php
use function Phunkie\Streams\Infinite\iterate;
use function Phunkie\Streams\Infinite\fromRange;

// Process a large finite range in chunks of 1000
$sum = 0;
for ($i = 0; $i < 5; $i++) {
    $chunkSum = Stream(fromRange($i * 1000 + 1, ($i + 1) * 1000))
        ->compile()
        ->fold(0)(fn($acc, $x) => $acc + $x);
    $sum += $chunkSum;
}
// $sum contains the sum of numbers from 1 to 5000
```

## When to Use Infinite Streams

Infinite streams are particularly useful for:

1. **Mathematical sequences**: Natural numbers, Fibonacci, geometric series
2. **Data generation**: Creating test data, simulation values
3. **Event streams**: Modeling continuous event flows
4. **Lazy evaluation**: Computing values only when needed
5. **Unbounded data processing**: Working with data whose size isn't known in advance

## Next Steps

- [Pure Streams](pure-streams.md): Learn more about working with finite streams
- [Working with Streams](working-with-streams.md): Explore more operations available for all stream types
- [Resource Streams](resource-streams.md): Understand how to work with external resources 