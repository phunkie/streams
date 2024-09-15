<?php

namespace Phunkie\Streams\Type;

use Phunkie\Streams\IO\IO;

class Scope
{
    private array $callables = [];
    private Transformation $transformation;

    public function appendTransformation(Transformation $transformation): void
    {
        if (!isset($this->transformation)) {
            $this->transformation = $transformation;
            return;
        }
        $this->transformation = $this->transformation->andThen($transformation);
    }

    public function runTransformations(iterable $chunk, $acceptIo = true): iterable | IO
    {
        if (!isset($this->transformation)) {
            return $chunk;
        }

        if ($this->transformation->isPassthrough()) {
            $io = $this->transformation->run($chunk);
            $io->run();
            if ($acceptIo) {
                return $io;
            }
            return $io->unsafeRunSync();
        }

        return $this->transformation->run($chunk);
    }
}
