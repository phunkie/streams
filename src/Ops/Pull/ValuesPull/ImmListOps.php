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

use function Phunkie\Streams\Functions\transformation\chunk;
use function Phunkie\Streams\Functions\transformation\dropWhile;
use function Phunkie\Streams\Functions\transformation\filter;
use function Phunkie\Streams\Functions\transformation\interleave;
use function Phunkie\Streams\Functions\transformation\takeWhile;

use Phunkie\Streams\Pull\ValuesPull;

/**
 * List-like operations for ValuesPull. Provides take, filter, interleave, and more.
 *
 * @method array getValues()
 * @method \Phunkie\Streams\Type\Scope getScope()
 */
trait ImmListOps
{
    /**
     * Take the first $n elements, returning a new ValuesPull with the same scope.
     *
     * @param int $n Number of elements to take
     * @return ValuesPull
     */
    public function take(int $n): ValuesPull
    {
        $valuesPull = new ValuesPull(
            ...array_slice($this->getValues(), 0, $n)
        );
        $valuesPull->setScope($this->getScope());

        return $valuesPull;
    }

    /**
     * Register a filter transformation to keep elements matching the predicate.
     *
     * @param callable $f A => bool
     * @return ValuesPull
     */
    public function filter(callable $f): ValuesPull
    {
        $this->appendTransformation(filter($f));

        return $this;
    }

    /**
     * Register an interleave transformation to alternate elements with other pulls.
     *
     * @param \Phunkie\Streams\Type\Pull ...$other Pulls to interleave with
     * @return ValuesPull
     */
    public function interleave(...$other): ValuesPull
    {
        $this->appendTransformation(interleave(...$other));

        return $this;
    }

    /**
     * Register a takeWhile transformation to emit elements while the predicate holds.
     *
     * @param callable $predicate A => bool
     * @return ValuesPull
     */
    public function takeWhile(callable $predicate): ValuesPull
    {
        $this->appendTransformation(takeWhile($predicate));

        return $this;
    }

    /**
     * Register a dropWhile transformation to skip elements while the predicate holds.
     *
     * @param callable $predicate A => bool
     * @return ValuesPull
     */
    public function dropWhile(callable $predicate): ValuesPull
    {
        $this->appendTransformation(dropWhile($predicate));

        return $this;
    }

    /**
     * Register a chunk transformation to group elements into arrays of the given size.
     *
     * @param int $size Number of elements per chunk
     * @return ValuesPull
     */
    public function chunk(int $size): ValuesPull
    {
        $this->appendTransformation(chunk($size));

        return $this;
    }
}
