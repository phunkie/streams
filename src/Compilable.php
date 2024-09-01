<?php

namespace Phunkie\Streams;

use Phunkie\Streams\IO\IO;
use Phunkie\Types\ImmList;

interface Compilable
{
    public function toList(): ImmList | IO;

    public function toArray(): array;
}
