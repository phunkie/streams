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
    use Phunkie\Streams\IO\OpenedRead;
    use Phunkie\Streams\Type\Stream;

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

    /**
     * A stream of chunks read from a handle that an IO opens.
     *
     * The IO runs on the first pull, not when the stream is built. The handle is closed when it
     * reaches EOF, when a transformation such as take() halts the stream early, and when an effect
     * throws, through the stream's Closeable.
     *
     * @param IO $open IO<resource>: opens the handle, for instance io(fn () => fopen('php://input', 'rb'))
     * @param int $chunk Bytes per pull
     * @return Stream
     */
    function readHandle(IO $open, int $chunk = 65536): Stream
    {
        return Stream::fromResourceObject(new OpenedRead($open), $chunk);
    }
}
