<?php

namespace Phunkie\Streams\Type;

use Phunkie\Streams\IO\IO;

class Scope
{
    private array $callables = [];
    private Pipeline $pipeline;

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
                'filter' => array_filter($chunk, $f),
                'interleave' => $f($chunk),
                'evalMap' => new IO(fn () => ImmList(...array_map($f, $chunk))),
                'evalFilter' => new IO(fn () => ImmList(...array_map(fn($x) => new IO(fn() =>$x),
                    array_filter($chunk, fn($v) => $f($v)->run())))),
                default => throw new \Error("Method not found $type")
            };
        }

        return $chunk;
    }

    public function addPipeline(Pipeline $pipeline)
    {
        if (!isset($this->pipeline)) {
            $this->pipeline = $pipeline;
            return;
        }
        $this->pipeline = $this->pipeline->andThen($pipeline);
    }

    public function runPipeline(array $chunk): array | IO
    {
        if (!isset($this->pipeline)) {
            return $chunk;
        }
        return $this->pipeline->run($chunk);
    }

}
