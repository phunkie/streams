<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

/**
 * @method getScope
 */
trait FunctorOps
{
    public function mapOutput($f): static
    {
        $this->getScope()->addMap($f);

        return $this;
    }
}