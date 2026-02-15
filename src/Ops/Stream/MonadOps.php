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
use Phunkie\Types\Kind;

/**
 * Monad operations for Stream. Provides flatMap, flatten, ap, and bind.
 *
 * @method getPull() Phunkie\Streams\Type\Pull
 */
trait MonadOps
{
    /**
     * Apply a function that returns a stream to each element, then flatten the results.
     *
     * @param callable $f A => Stream<B>
     * @return Kind|Stream
     */
    public function flatMap(callable $f): Kind | Stream
    {
        $this->getPull()->flatMap($f);

        return $this;
    }

    /**
     * Flatten a stream of streams into a single stream.
     *
     * @return Kind|Stream
     */
    public function flatten(): Kind | Stream
    {
        $this->getPull()->flatten();

        return $this;
    }

    /**
     * Applicative apply: apply a stream of functions to this stream of values.
     *
     * @param Kind $f Stream of callable (A => B)
     * @return Kind|Stream
     */
    public function ap(Kind $f): Kind | Stream
    {
        return $f->flatMap(fn ($g) => $this->map($g));
    }

    /**
     * Alias for flatMap.
     *
     * @param callable $f A => Stream<B>
     * @return Kind|Stream
     */
    public function bind(callable $f): Kind | Stream
    {
        return $this->flatMap($f);
    }
}
