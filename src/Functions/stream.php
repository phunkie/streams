<?php

namespace {

    use Phunkie\Streams\Infinite\Infinite;
    use Phunkie\Streams\IO\File\Path;
    use Phunkie\Streams\Type\Stream;

    function Stream(...$t): Stream {
        if (count($t) === 1 && $t[0] instanceof Path) {
            return Stream::fromResource($t[0]);
        }

        if (count($t) === 1 && $t[0] instanceof Infinite) {
            return Stream::fromInfinite($t[0]);
        }

        return Stream::fromValues(...$t);
    }
}
