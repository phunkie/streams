<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace {

    use Phunkie\Streams\Infinite\Constant;
    use Phunkie\Streams\Infinite\Infinite;
    use Phunkie\Streams\Infinite\Iterate;
    use Phunkie\Streams\Infinite\Range;
    use Phunkie\Streams\Infinite\Repeat;
    use Phunkie\Streams\Infinite\Timer;
    use Phunkie\Streams\Infinite\Unfold;

    /**
     * Create an infinite stream from a numeric range.
     *
     * @param int $start Starting value (inclusive)
     * @param int $end   Ending value (inclusive, defaults to PHP_INT_MAX)
     * @param int $step  Step increment between values
     * @return Infinite
     */
    function fromRange(int $start, int $end = PHP_INT_MAX, int $step = 1): Infinite
    {
        return new Range($start, $end, $step);
    }

    /**
     * Create an infinite stream by repeatedly applying a function to a seed.
     *
     * Returns a curried function: iterate($start)($f) produces $start, $f($start), $f($f($start)), ...
     *
     * @param int $start The initial seed value
     * @return \Closure(callable): Iterate
     */
    function iterate(int $start)
    {
        return function (callable $f) use ($start) {
            return new Iterate($f, $start);
        };
    }

    /**
     * Create an infinite stream by unfolding a seed with a function.
     *
     * Returns a curried function: unfold($seed)($f) where $f returns the next value and new state.
     *
     * @param mixed $seed The initial state
     * @return \Closure(callable): Unfold
     */
    function unfold($seed)
    {
        return function (callable $f) use ($seed) {
            return new Unfold($f, $seed);
        };
    }

    /**
     * Create an infinite stream that endlessly emits a constant value.
     *
     * @param mixed $pattern The value to repeat
     * @return Infinite
     */
    function fromConstant(mixed $pattern): Infinite
    {
        return new Constant($pattern);
    }

    /**
     * Create an infinite stream that cycles through the given values repeatedly.
     *
     * @param mixed ...$values The values to cycle through
     * @return Infinite
     */
    function repeat(...$values): Infinite
    {
        return new Repeat(...$values);
    }

    /**
     * Create an infinite stream that emits a tick at a fixed time interval.
     *
     * @param float    $seconds Time interval in seconds between emissions
     * @param int|null $stopAt  Optional maximum number of ticks before stopping
     * @return Infinite
     */
    function awakeEvery(float $seconds, $stopAt = null): Infinite
    {
        return new Timer($seconds, $stopAt);
    }
}
