<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

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
        $this->getScope()->addCallable('map', $f);

        return $this;
    }
}