<?php

namespace Phunkie\Streams\Type;

use Phunkie\Types\Function1;
use const Phunkie\Functions\function1\identity;

class Scope
{
    private array $maps;

    public function __construct()
    {
        $this->maps[] = identity;
    }

    public function addMap($f)
    {
        $this->maps[] = $f;
    }

    public function getMaps(): array
    {
        return $this->maps;
    }
}