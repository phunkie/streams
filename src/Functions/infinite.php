<?php

namespace {

    use Phunkie\Streams\Infinite\Range;
    use Phunkie\Streams\Type\Stream;

    function fromRange(int $start, int $end, int $step = 1): Stream
    {
        return Stream::fromInfinite(new Range($start, $end, $step));
    }
}