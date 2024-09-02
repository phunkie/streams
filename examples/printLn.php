<?php

use function Phunkie\Functions\show\show;
use function Phunkie\Functions\show\showType;

if (!function_exists('printLn')) {
    function printLn($value)
    {
        echo showType($value) . ": ";
        show($value);
        echo PHP_EOL;
    }
}
