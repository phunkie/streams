# Phunkie Streams #

Phunkie Streams is a PHP functional library to work with Streams

Installation

```
composer require phunkie/streams
```


## Pure Streams

```php

// Stream<Pure, Int>
$stream = Stream(1, 2, 3, 4);

// Compile pure streams

$stream->compile->toList();
// List<Int> (1, 2, 3, 4)

$stream->compile->toArray();
// [1, 2, 3, 4]
```

## Transformations on Pure Streams

```php
use const Phunkie\Functions\numbers\increment;

$stream = Stream(1, 2, 3, 4);

$stream->map(increment)->compile->toList();
// List<Int> (2, 3, 4, 5)

Stream(1, 2, 3, 4)
    ->zipWith(increment)
    ->compile
    ->toList();
// List(Pair(1, 2), Pair(2, 3), Pair(3, 4), Pair(4, 5))

```

## Read from a stream

```php

readAll("homer-odyssey-ch-1.txt", 3)
    ->runLog
    ->unsafeRun();

// [
// 'Tel',
// 'l m',
// 'e, ',
// 'O M',
// 'use',
// ', of',
// ' th',
// 'e m',
// 'an ',
// '...',
// ]
```