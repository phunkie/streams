<?php

namespace Phunkie\Streams\Ops\Pull\ResourcePull;

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
