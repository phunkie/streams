<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use Phunkie\Streams\Pull\ValuesPull;

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
        $valuesPull = new ValuesPull(
            ...array_filter($this->getValues(), $f)
        );
        $valuesPull->setScope($this->getScope());
        return $valuesPull;
    }
}