<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use Phunkie\Streams\Pull\ValuesPull;
use function Phunkie\Streams\Functions\pipeline\filter;
use function Phunkie\Streams\Functions\pipeline\interleave;

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
        $this->addPipeline(filter($f));

        return $this;
    }

    public function interleave(... $other): ValuesPull
    {
        $this->addPipeline(interleave(...$other));

        return $this;
    }
}
