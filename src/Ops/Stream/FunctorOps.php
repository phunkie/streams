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
 * Functor operations for Stream. Provides element-wise mapping over stream values.
 *
 * @method getPull() Phunkie\Streams\Type\Pull
 * @method as($b) Phunkie\Streams\Type\Stream
 */
trait FunctorOps
{
    use \Phunkie\Ops\FunctorOps;

    /**
     * Apply a function to each element of the stream.
     *
     * @param callable $f A => B
     * @return Kind|Stream
     */
    public function map($f): Kind | Stream
    {
        $this->getPull()->map($f);

        return $this;
    }

    /**
     * Invariant map: apply covariant function $f (ignores contravariant $g).
     *
     * @param callable $f A => B
     * @param callable $g B => A (unused, kept for interface compliance)
     * @return Kind|Stream
     */
    public function imap(callable $f, callable $g): Kind | Stream
    {
        return $this->map($f);
    }
}
