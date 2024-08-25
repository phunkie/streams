<?php

namespace Phunkie\Streams\Ops\Pull\InfinitePull;

/**
 * @method getInfinite
 */
trait InteratorOps
{
    public function current(): mixed
    {
        return $this->getInfinite()->getValues()->current();
    }

    public function next(): void
    {
        $this->getInfinite()->getValues()->next();
    }

    public function key(): mixed
    {
        return $this->getInfinite()->getValues()->key();
    }

    public function valid(): bool
    {
        return $this->getInfinite()->getValues()->valid();
    }

    public function rewind(): void
    {
        $this->getInfinite()->getValues()->rewind();
    }
}