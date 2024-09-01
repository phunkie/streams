<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use function Phunkie\Streams\Functions\pipeline\map;

/**
 * @method getScope
 */
trait FunctorOps
{
    public function mapOutput($f): static
    {
        $this->getScope()->addCallable('map', $f);

        return $this;
    }

    public function map($f): static
    {
        $this->addPipeline(map($f));

        return $this;
    }
}
