<?php

namespace Phunkie\Streams;

use Phunkie\Streams\Stream\Compiler;
use Phunkie\Types\ImmList;

interface Compilable
{
    public function toList(): ImmList;

    public function toArray(): array;
}