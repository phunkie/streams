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

/**
 * Iterator interface implementation for ValuesPull. Enables sequential element access.
 *
 * @method array getValues()
 * @method int getIndex()
 * @method void setIndex(int $index)
 */
trait IteratorOps
{
    /**
     * Reset the iterator to the first element.
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->setIndex(0);
    }

    /**
     * Return the current element.
     *
     * @return mixed
     */
    public function current(): mixed
    {
        return $this->getValues()[$this->index];
    }

    /**
     * Return the current index.
     *
     * @return mixed
     */
    public function key(): mixed
    {
        return $this->getIndex();
    }

    /**
     * Check if the current position is valid.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->getIndex() < count($this->getValues());
    }

    /**
     * Advance to the next element.
     *
     * @return void
     * @throws \OutOfBoundsException If no more elements are available
     */
    public function next(): void
    {
        if (!$this->valid()) {
            throw new \OutOfBoundsException("No more elements to pull.");
        }

        $this->setIndex($this->getIndex() + 1);
    }
}
