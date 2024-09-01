<?php

namespace Phunkie\Streams\Ops\Pull\InfinitePull;

use function Phunkie\Streams\Functions\pipeline\map;

/**
 * @method getScope
 */
trait FunctorOps
{
    public function mapOutput($f): static
    {
        return $this->map($f);
    }

    public function map($f): static
    {
        $this->addPipeline(map($f));

        return $this;
    }
}
