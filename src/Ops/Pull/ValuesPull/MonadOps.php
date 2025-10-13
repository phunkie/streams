<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use function Phunkie\Streams\Functions\transformation\flatMap;
use function Phunkie\Streams\Functions\transformation\flatten;

/**
 * @method getScope
 */
trait MonadOps
{
    public function flatMap(callable $f): static
    {
        $this->appendTransformation(flatMap($f));

        return $this;
    }

    public function flatten(): static
    {
        $this->appendTransformation(flatten());

        return $this;
    }
}
