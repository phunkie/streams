<?php

namespace Phunkie\Streams\Type;

use Phunkie\Streams\IO\IO;
use Phunkie\Types\ImmList;
use const Phunkie\Functions\function1\identity;

class Scope
{
    private array $callables = [];

    public function addCallable($type, $f)
    {
        $this->callables[] = [$type, $f];
    }

    public function getCallables(): array
    {
        return $this->callables;
    }

    public function apply(array $chunk): array | IO
    {
        foreach ($this->getCallables() as $callable) {
            [$type, $f] = $callable;
            $chunk = match ($type) {
                'map' => array_map($f, $chunk),
                'evalMap' => new IO(fn () => ImmList(...array_map($f, $chunk))),
                'filter' => array_filter($chunk, $f),
                default => throw new \Error("Method not found $type")
            };
        }

        return $chunk;
    }
}
