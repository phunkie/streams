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

    public function runPipeline(array $chunk): array | IO
    {
        if (!isset($this->pipeline)) {
            return $chunk;
        }

        if ($this->pipeline->isPassthrough()) {
            $io = $this->pipeline->run($chunk);
            $io->run();
            return $chunk;
        }

        return $this->pipeline->run($chunk);
    }

}
