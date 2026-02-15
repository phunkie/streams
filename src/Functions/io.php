<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Functions\io {

    use Phunkie\Effect\IO\IO;

    /**
     * Wrap a closure in an IO effect.
     *
     * @param callable $f The side-effecting closure to wrap
     * @return IO
     */
    function io($f): IO
    {
        return new IO($f);
    }
}
