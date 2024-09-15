<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use Phunkie\Streams\Pull\ValuesPull;
use function Phunkie\Streams\Functions\transformation\filter;
use function Phunkie\Streams\Functions\transformation\interleave;

trait ImmListOps
{
    public function take(int $n): ValuesPull
    {
        $valuesPull = new ValuesPull(
            ...array_slice($this->getValues(), 0, $n)
        );
        $valuesPull->setScope($this->getScope());
        return $valuesPull;
    }

    public function filter(callable $f): ValuesPull
    {
        $this->appendTransformation(filter($f));

        return $this;
    }

    public function interleave(... $other): ValuesPull
    {
        $this->appendTransformation(interleave(...$other));

        return $this;
    }
}
