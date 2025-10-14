<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

trait IteratorOps
{
    public function rewind(): void
    {
        $this->setIndex(0);
    }

    public function current(): mixed
    {
        return $this->getValues()[$this->index];
    }

    public function key(): mixed
    {
        return $this->getIndex();
    }

    public function valid(): bool
    {
        return $this->getIndex() < count($this->getValues());
    }

    public function next(): void
    {
        if (!$this->valid()) {
            throw new \OutOfBoundsException("No more elements to pull.");
        }

        $this->setIndex($this->getIndex() + 1);
    }
}
