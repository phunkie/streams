<?php

namespace Phunkie\Streams\Type;

use Phunkie\Streams\IO\IO;

class Scope
{
    private array $callables = [];
    private Pipeline $pipeline;

    public function addPipeline(Pipeline $pipeline): void
    {
        if (!isset($this->pipeline)) {
            $this->pipeline = $pipeline;
            return;
        }
        $this->pipeline = $this->pipeline->andThen($pipeline);
    }

    public function runPipeline(iterable $chunk, $acceptIo = true): iterable | IO
    {
        if (!isset($this->pipeline)) {
            return $chunk;
        }

        if ($this->pipeline->isPassthrough()) {
            $io = $this->pipeline->run($chunk);
            $io->run();
            if ($acceptIo) {
                return $io;
            }
            return $io->unsafeRunSync();
        }

        return $this->pipeline->run($chunk);
    }

    public function getPipeline(): Pipeline
    {
        return $this->pipeline;
    }

}
