<?php

use function Phunkie\Streams\Functions\file\readAll;

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/printLn.php';


//readAll("large_file.txt", 1);


class Pair
{
    public function __construct(public $a, public $b)
    {
    }

    public function __get($prop)
    {
        return $prop === '_1' ? $this->a : $this->b;
    }
}

$p = new Pair(0, 1);
$f = function ($p) {
    return new Pair($p->_1, new Pair($p->_2, $p->_1 + $p->_2));
};
$result = $f($p);
echo 'Value: ' . $result->_1 . ', Next seed: ' . $result->_2->_1 . ', ' . $result->_2->_2 . PHP_EOL;
