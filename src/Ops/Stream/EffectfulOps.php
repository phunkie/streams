<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Stream;

use Phunkie\Streams\Type\Stream;

/**
 * Effectful operations for Stream. Wraps IO-producing functions into the stream pipeline.
 *
 * @method \Phunkie\Streams\Type\Pull getPull()
 */
trait EffectfulOps
{
    /**
     * Apply an effectful function to each element, using the IO result as the new value.
     *
     * @param callable $f A => IO<B>
     * @return Stream
     */
    public function evalMap($f): Stream
    {
        $this->getPull()->evalMap($f);

        return $this;
    }

    /**
     * Apply an effectful function for side effects, keeping the original element.
     *
     * @param callable $f A => IO<void>
     * @return Stream
     */
    public function evalTap($f): Stream
    {
        $this->getPull()->evalTap($f);

        return $this;
    }

    /**
     * Filter elements using an effectful predicate that returns IO<bool>.
     *
     * @param callable $f A => IO<bool>
     * @return Stream
     */
    public function evalFilter($f): Stream
    {
        $this->getPull()->evalFilter($f);

        return $this;
    }

    /**
     * FlatMap with an effectful function that returns IO<Stream>.
     *
     * @param callable $f A => IO<Stream<B>>
     * @return Stream
     */
    public function evalFlatMap($f): Stream
    {
        $this->getPull()->evalFlatMap($f);

        return $this;
    }
}
