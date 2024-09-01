<?php

namespace Phunkie\Streams\Ops\Pull\InfinitePull;

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
}
