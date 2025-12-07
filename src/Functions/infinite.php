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

    function fromRange(int $start, int $end = PHP_INT_MAX, int $step = 1): Infinite
    {
        return new Range($start, $end, $step);
    }

    function iterate(int $start)
    {
        return function (callable $f) use ($start) {
            return new Iterate($f, $start);
        };
    }

    function unfold($seed)
    {
        return function (callable $f) use ($seed) {
            return new Unfold($f, $seed);
        };
    }

    function fromConstant(mixed $pattern): Infinite
    {
        return new Constant($pattern);
    }

    function repeat(...$values): Infinite
    {
        return new Repeat(...$values);
    }

    function awakeEvery(float $seconds, $stopAt = null): Infinite
    {
        return new Timer($seconds, $stopAt);
    }
}

namespace Phunkie\Streams\Functions\infinite {

    use Phunkie\Streams\Infinite\Infinite;

    const fromRange = '\\Phunkie\\Streams\\Functions\\infinite\\fromRange';
    function fromRange(int $start, int $end = PHP_INT_MAX, int $step = 1): Infinite
    {
        return \fromRange($start, $end, $step);
    }

    const iterate = '\\Phunkie\\Streams\\Functions\\infinite\\iterate';
    function iterate(int $start): callable
    {
        return \iterate($start);
    }

    const unfold = '\\Phunkie\\Streams\\Functions\\infinite\\unfold';
    function unfold($seed): callable
    {
        return \unfold($seed);
    }

    const fromConstant = '\\Phunkie\\Streams\\Functions\\infinite\\fromConstant';
    function fromConstant(mixed $pattern): Infinite
    {
        return \fromConstant($pattern);
    }

    const repeat = '\\Phunkie\\Streams\\Functions\\infinite\\repeat';
    function repeat(...$values): Infinite
    {
        return \repeat(...$values);
    }

    const awakeEvery = '\\Phunkie\\Streams\\Functions\\infinite\\awakeEvery';
    function awakeEvery(float $seconds, $stopAt = null): Infinite
    {
        return \awakeEvery($seconds, $stopAt);
    }
}
