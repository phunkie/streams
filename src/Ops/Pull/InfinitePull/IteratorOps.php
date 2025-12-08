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
 * @method Infinite getInfinite()
 */
trait IteratorOps
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
