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

use Phunkie\Streams\Infinite\Timer;
use Phunkie\Streams\Pull\InfinitePull;
use Phunkie\Streams\Pull\ValuesPull;

/**
 * List-like operations for InfinitePull. Converts to finite ValuesPull when bounded.
 *
 * @method \Phunkie\Streams\Infinite\Infinite getInfinite()
 * @method \Phunkie\Streams\Type\Scope getScope()
 * @method bool valid()
 * @method mixed pull()
 */
trait ImmListOps
{
    /**
     * Take the first $n elements from the infinite source.
     * Returns InfinitePull for Timer sources (preserving timing), or ValuesPull otherwise.
     *
     * @param int $n Number of elements to take
     * @return InfinitePull|ValuesPull
     */
    public function take(int $n): InfinitePull | ValuesPull
    {
        $infinite = $this->getInfinite();

        if ($infinite instanceof Timer) {
            $smallerInfinite = new InfinitePull(
                awakeEvery($infinite->getSeconds(), $n)
            );
            $smallerInfinite->setScope($this->getScope());

            return $smallerInfinite;
        }

        $values = [];

        for ($i = 0; $i < $n; $i++) {
            if ($this->valid()) {
                $values[] = $this->pull();
            }
        }

        $infinite->reset();

        $valuesPull = new ValuesPull(
            ...$values
        );
        $valuesPull->setScope($this->getScope());

        return $valuesPull;
    }
}
