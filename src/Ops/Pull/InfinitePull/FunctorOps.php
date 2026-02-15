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

use function Phunkie\Streams\Functions\transformation\map;

use Phunkie\Streams\Type\Scope;

/**
 * Functor operations for InfinitePull. Registers map transformations on the scope.
 *
 * @method Scope getScope()
 */
trait FunctorOps
{
    /**
     * Alias for map. Apply a function to each output element.
     *
     * @param callable $f A => B
     * @return static
     */
    public function mapOutput($f): static
    {
        return $this->map($f);
    }

    /**
     * Register a map transformation to be applied when the pull is compiled.
     *
     * @param callable $f A => B
     * @return static
     */
    public function map($f): static
    {
        $this->appendTransformation(map($f));

        return $this;
    }
}
