<?php

namespace Phunkie\Streams\Functions\io {

    use Phunkie\Effect\IO\IO;

    function io($f): IO
    {
        return new IO($f);
    }
}
