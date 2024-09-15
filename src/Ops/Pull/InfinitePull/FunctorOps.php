<?php

namespace Phunkie\Streams\Ops\Pull\InfinitePull;

use function Phunkie\Streams\Functions\transformation\map;

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
        $this->appendTransformation(map($f));

        return $this;
    }
}
