<?php

namespace {

    use Phunkie\Streams\Infinite\Infinite;
    use Phunkie\Streams\Infinite\Iterate;
    use Phunkie\Streams\Infinite\Range;
    use Phunkie\Streams\Infinite\Constant;
    use Phunkie\Streams\Infinite\Repeat;

    function fromRange(int $start, int $end, int $step = 1): Infinite
    {
        return new Range($start, $end, $step);
    }

    function iterate(int $start) {
        return function (callable $f) use ($start) {
            return new Iterate($f, $start);
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
}
