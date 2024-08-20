<?php

namespace Phunkie\Streams\Type;

use Phunkie\Streams\Compilable;
use Phunkie\Streams\Showable;

interface Pull extends Showable, Compilable, \Iterator
{
    public function pull();

    public function toStream(): Stream;
}