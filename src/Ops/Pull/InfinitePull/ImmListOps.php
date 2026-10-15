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

use function Phunkie\Streams\Functions\transformation\take;

use Phunkie\Streams\Infinite\Timer;
use Phunkie\Streams\Pull\InfinitePull;

/**
 * List-like operations for InfinitePull.
 *
 * @method \Phunkie\Streams\Infinite\Infinite getInfinite()
 * @method \Phunkie\Streams\Type\Scope getScope()
 * @method static appendTransformation(\Phunkie\Streams\Type\Transformation $transformation)
 */
trait ImmListOps
{
    /**
     * Take the first $n elements from the infinite source, pulling no further once they are taken.
     * A Timer source becomes a bounded timer so its timing is preserved.
     *
     * @param int $n Number of elements to take
     * @return InfinitePull
     */
    public function take(int $n): InfinitePull
    {
        $infinite = $this->getInfinite();

        if ($infinite instanceof Timer) {
            $bounded = new InfinitePull(awakeEvery($infinite->getSeconds(), $n));
            $bounded->setScope($this->getScope());

            return $bounded;
        }

        $this->appendTransformation(take($n));

        return $this;
    }
}
