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
use function Phunkie\Streams\Functions\transformation\take;
use function Phunkie\Streams\Functions\transformation\takeWhile;

/**
 * List-like operations for ValuesPull. Provides take, filter, interleave, and more.
 *
 * @method array getValues()
 * @method \Phunkie\Streams\Type\Scope getScope()
 */
trait ImmListOps
{
    /**
     * Register a take transformation to keep only the first $n elements.
     *
     * @param int $n Number of elements to take
     * @return static
     */
    public function take(int $n): static
    {
        $this->appendTransformation(take($n));

        return $this;
    }

    /**
     * Register a filter transformation to keep elements matching the predicate.
     *
     * @param callable $f A => bool
     * @return static
     */
    public function filter(callable $f): static
    {
        $this->appendTransformation(filter($f));

        return $this;
    }

    /**
     * Register an interleave transformation to alternate elements with other pulls.
     *
     * @param \Phunkie\Streams\Type\Pull ...$other Pulls to interleave with
     * @return static
     */
    public function interleave(...$other): static
    {
        $this->appendTransformation(interleave(...$other));

        return $this;
    }

    /**
     * Register a takeWhile transformation to emit elements while the predicate holds.
     *
     * @param callable $predicate A => bool
     * @return static
     */
    public function takeWhile(callable $predicate): static
    {
        $this->appendTransformation(takeWhile($predicate));

        return $this;
    }

    /**
     * Register a dropWhile transformation to skip elements while the predicate holds.
     *
     * @param callable $predicate A => bool
     * @return static
     */
    public function dropWhile(callable $predicate): static
    {
        $this->appendTransformation(dropWhile($predicate));

        return $this;
    }

    /**
     * Register a chunk transformation to group elements into arrays of the given size.
     *
     * @param int $size Number of elements per chunk
     * @return static
     */
    public function chunk(int $size): static
    {
        $this->appendTransformation(chunk($size));

        return $this;
    }
}
