<?php

namespace Phunkie\Streams\Functions\io {

    use Phunkie\Streams\IO\IO;

    function io($f): IO
    {
        return new IO($f);
    }
}
