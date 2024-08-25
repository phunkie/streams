<?php

use Phunkie\Streams\Type\Range;
use const Phunkie\Functions\numbers\increment;

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/printLn.php';

$stream = Stream(1, 2, 3, 4);
printLn($stream);

$plusOne = $stream->map(increment);
printLn($plusOne->compile->toList());

$smaller = $plusOne->take(2);

printLn($smaller->compile->toList());

printLn($stream->compile->runLog());

$a = Stream("John", "Yoko");
$b = Stream("Paul", "Linda");

printLn($a->concat($b)->compile->toList());

$long = fromRange(1,100000000000);
printLn($long);
printLn($long->take(50)->compile->toList());

$x = Stream(1, 2, 3, 4, 5);

//printLn($x->evalMap(fn($x) => $x * 2)
//    ->evalFilter(fn($x) => $x < 5)
//    ->compile
//    ->toList());

//$y = Stream("Monday", "Tuesday", "Wednesday", "Thursday", "Friday");
//
//printLn($x->interleave($y)->compile->toList());
// Stream<Pure, Mixed> List(1, "Monday", 2, "")

