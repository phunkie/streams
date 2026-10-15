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

use Phunkie\Effect\IO\IO;

/**
 * Folding a stream into one value, the way ImmList folds, except that the result is an IO:
 * the elements are pulled and the fold runs when the IO does.
 *
 * @method \Phunkie\Streams\Stream\Compiler compile()
 */
trait FoldableOps
{
    /**
     * Partially applied like ImmList::foldLeft: foldLeft($initial)($f) is IO<B>.
     *
     * @template B
     * @param B $initial
     * @return callable(callable(B, mixed): B): IO<B>
     */
    public function foldLeft(mixed $initial): callable
    {
        return fn (callable $f): IO => $this->compile()->fold($initial, $f);
    }
}
