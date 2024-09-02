<?php

use Phunkie\Streams\IO\IO;
use Phunkie\Streams\Type\Range;
use function Phunkie\Functions\io\io;
use const Phunkie\Functions\numbers\increment;

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/printLn.php';

$stream = Stream(1, 2, 3, 4);
printLn($stream);

$plusOne = $stream->map(increment);
printLn($plusOne->compile->toList);

$smaller = $plusOne->take(2);

printLn($smaller->compile->toList);

printLn($stream->compile->runLog);

$a = Stream("John", "Yoko");
$b = Stream("Paul", "Linda");

printLn($a->concat($b)->compile->toList);

$long = Stream(fromRange(1,100000000000));
printLn($long);
printLn($long->take(50)->compile->toList);

$infinite = Stream(iterate(0)(increment));
//printLn("Infinite stream:");
printLn($infinite);
printLn($infinite->take(10)->compile->toList);

printLn(Stream(1, 2, 3)->repeat->take(10)->compile->toList);

printLn(Stream(1, 2, 3, 4, 5)->map(fn($x) => $x * 2)->compile->toList);

printLn(Stream(1, 2, 3, 4, 5)->filter(fn($x) => $x % 2 == 0)->compile->toList);

printLn(Stream(1, 2, 3, 4, 5)->evalMap(fn($x) => io(fn() => $x * 2))
    ->compile
    ->toList
    ->unsafeRunSync);

$x = Stream(1, 2, 3, 4, 5);
$y = Stream("Monday", "Tuesday", "Wednesday", "Thursday", "Friday");
$z = Stream(true, false, true, false, true);

printLn($x->interleave($y, $z)->compile->toList);

printLn(Stream(1, 2, 3, 4)
    ->zipWith(increment)
    ->compile
    ->toList());

printLn($y->evalTap(fn($x) => new IO(fn() => printLn(strlen($x))))->compile->drain->unsafeRunSync);