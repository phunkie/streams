<?php

namespace Phunkie\Streams\IO\File {

    use Phunkie\Streams\IO\Read;
    use Phunkie\Streams\Type\Stream;

    function readAll($path, $chunk): Stream
    {
        return Read::readAll($path, $chunk);
    }
}
