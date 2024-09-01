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

$stream->compile()->toList();
// List<Int> (1, 2, 3, 4)

$stream->compile()->toArray();
// [1, 2, 3, 4]
```

## Transformations on Pure Streams

```php
use const Phunkie\Functions\numbers\increment;

$stream = Stream(1, 2, 3, 4);

$stream->map(increment)->compile()->toList();
// List<Int> (2, 3, 4, 5)

Stream(1, 2, 3, 4)
    ->zipWith(increment)
    ->compile()
    ->toList();
// List(Pair(1, 2), Pair(2, 3), Pair(3, 4), Pair(4, 5))

```

## Infinite streams

```php

$fromRange = Stream(fromRange(0, 1000000000));
$fromRange->take(10)->compile()->toList();
// List(0, 1, 2, 3, 4, 5, 6, 7, 8, 9) 

$infiniteOds = Stream(iterate(1)(fn ($x) => $x + 2));
$infiniteOds->take(10)->compile()->toList();
// List(1, 3, 5, 7, 9, 11, 13, 15, 17, 19)

$repeat = Stream(1, 2, 3)
    ->repeat()->take(12)->compile()->toList();
// List(1, 2, 3, 1, 2, 3, 1, 2, 3, 1, 2, 3)

```

## Picking on infinite streams with runlog

```php

Stream(1, 2, 3)
    ->repeat()
    ->runLog()
    ->unsafeRun();
// [1, 2, 3, 1, 2, 3, 1, 2, 3, ...] 

````

## Combine streams

```php

Stream(1, 2, 3)
    ->concat(Stream(4, 5, 6))
    ->compile
    ->toList();
// List(1, 2, 3, 4, 5, 6)

$x = Stream(1, 2, 3, 4, 5);
$y = Stream("Monday", "Tuesday", "Wednesday", "Thursday", "Friday");
$z = Stream(true, false, true, false, true);

$x->interleave($y, $z)->compile()->toList();
// List(1, "Monday", true, 2, "Tuesday", false, 3, "Wednesday", true, 4,
//"Thursday", false, 5, "Friday", true)

```
