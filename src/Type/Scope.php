<?php

namespace Phunkie\Streams\Type;

use Phunkie\Effect\IO\IO;

class Scope
{
    private array $callables = [];
    private array $maps = [];
    private array $filters = [];
    private Transformation $transformation;

    public function appendTransformation(Transformation $transformation): void
    {
        if (!isset($this->transformation)) {
            $this->transformation = $transformation;

            return;
        }
        $this->transformation = $this->transformation->andThen($transformation);
    }

    public function addMap(callable $f): void
    {
        $this->maps[] = $f;
    }

    public function getMaps(): array
    {
        return $this->maps;
    }

    public function addFilter(callable $f): void
    {
        $this->filters[] = $f;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function runTransformations(iterable $chunk, $acceptIo = true): iterable | IO
    {
        if (!isset($this->transformation)) {
            return $chunk;
        }

        if ($this->transformation->isPassthrough()) {
            $io = $this->transformation->run($chunk);
            $io->unsafeRun();
            if ($acceptIo) {
                return $io;
            }

            return $io->unsafeRunSync();
        }

        return $this->transformation->run($chunk);
    }
}
