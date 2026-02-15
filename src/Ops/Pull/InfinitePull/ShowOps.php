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

/**
 * Show operations for InfinitePull. Previews the first 10 elements for display.
 *
 * @method \Phunkie\Streams\Infinite\Infinite getInfinite()
 */
trait ShowOps
{
    /**
     * Return the type representation.
     *
     * @return string
     */
    public function showType(): string
    {
        return 'Byte';
    }

    /**
     * Return a string showing the first 10 elements followed by "...".
     * Resets the infinite source after peeking.
     *
     * @return string
     */
    public function toString(): string
    {
        $values = [];
        for ($i = 0; $i < 10; $i++) {
            $values[] = $this->getInfinite()->getValues()->current();
            $this->getInfinite()->getValues()->next();
        }
        $this->getInfinite()->reset();

        return '[' . implode(', ', $values) . ', ...]';
    }
}
