<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull\ResourcePull;

use Phunkie\Streams\Type\Scope;

/**
 * Functor operations for ResourcePull. Registers maps on the scope for deferred execution.
 *
 * @method Scope getScope()
 */
trait FunctorOps
{
    /**
     * Register a mapping function to be applied to each output element at compile time.
     *
     * @param callable $f A => B
     * @return static
     */
    public function mapOutput($f): static
    {
        $this->getScope()->addMap($f);

        return $this;
    }
}
