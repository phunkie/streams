<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use Phunkie\Streams\IO\IO;
use Phunkie\Streams\Type\Pipeline;

/**
 * @method getScope(): Phunkie\Streams\Type\Scope
 */
trait PipelineOps
{
    public function addPipeline(Pipeline $pipeline): static
    {
        $this->getScope()->addPipeline($pipeline);
        return $this;
    }

    public function runPipeline(array $chunk): array | IO
    {
        return $this->getScope()->runPipeline($chunk);
    }
}