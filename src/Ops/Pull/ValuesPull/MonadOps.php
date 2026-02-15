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

use function Phunkie\Streams\Functions\transformation\flatMap;
use function Phunkie\Streams\Functions\transformation\flatten;

use Phunkie\Streams\Type\Scope;

/**
 * Monad operations for ValuesPull. Registers flatMap and flatten transformations.
 *
 * @method Scope getScope()
 */
trait MonadOps
{
    /**
     * Register a flatMap transformation. The function should return an iterable for each element.
     *
     * @param callable $f A => iterable<B>
     * @return static
     */
    public function flatMap(callable $f): static
    {
        $this->appendTransformation(flatMap($f));

        return $this;
    }

    /**
     * Register a flatten transformation to unwrap nested iterables.
     *
     * @return static
     */
    public function flatten(): static
    {
        $this->appendTransformation(flatten());

        return $this;
    }
}
