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

    use Phunkie\Streams\Infinite\Infinite;
    use Phunkie\Streams\IO\File\Path;
    use Phunkie\Streams\IO\Resource;
    use Phunkie\Streams\Type\Stream;

    /**
     * Factory function for creating streams.
     *
     * Accepts a Pull, Path, Infinite, Resource, or plain values.
     * - Pull: creates a stream backed by a pull-based source
     * - Path: creates a stream that reads from a file path
     * - Infinite: creates a stream from an infinite generator (range, iterate, unfold, etc.)
     * - Resource: creates a stream from a resource object
     * - Plain values: creates a finite stream emitting the given values
     *
     * @param mixed ...$t A single Pull|Path|Infinite|Resource, or one or more plain values
     * @return Stream
     */
    function Stream(...$t): Stream
    {
        if (count($t) === 1 && $t[0] instanceof \Phunkie\Streams\Type\Pull) {
            return Stream::fromPull($t[0]);
        }

        if (count($t) === 1 && $t[0] instanceof Path) {
            return Stream::fromResource($t[0]);
        }

        if (count($t) === 1 && $t[0] instanceof Infinite) {
            return Stream::fromInfinite($t[0]);
        }

        if (count($t) === 1 && $t[0] instanceof Resource) {
            return Stream::fromResourceObject($t[0]);
        }

        return Stream::fromValues(...$t);
    }
}
