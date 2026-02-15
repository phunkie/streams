<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull\InfinitePull;

use Phunkie\Streams\Infinite\Infinite;

/**
 * Iterator interface implementation for InfinitePull. Delegates to the underlying Infinite generator.
 *
 * @method Infinite getInfinite()
 */
trait IteratorOps
{
    /**
     * Return the current element from the infinite source.
     *
     * @return mixed
     */
    public function current(): mixed
    {
        return $this->getInfinite()->getValues()->current();
    }

    /**
     * Advance to the next element.
     *
     * @return void
     */
    public function next(): void
    {
        $this->getInfinite()->getValues()->next();
    }

    /**
     * Return the current key.
     *
     * @return mixed
     */
    public function key(): mixed
    {
        return $this->getInfinite()->getValues()->key();
    }

    /**
     * Check if the current position is valid.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->getInfinite()->getValues()->valid();
    }

    /**
     * Reset the iterator to the beginning.
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->getInfinite()->getValues()->rewind();
    }
}
