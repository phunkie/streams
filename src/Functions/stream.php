<?php

namespace {
    use Phunkie\Streams\Type\Stream;

    function Stream(...$t): Stream {
        return Stream::instance(...$t);
    }
}
