<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams;

/**
 * Something a stream holds open while it runs: a file handle, a socket, a statement's cursor.
 *
 * The compile loop closes it exactly once, when the stream ends, when a transformation halts it
 * early, or when an effect throws. After close() the source yields EOF; closing again does nothing.
 */
interface Closeable
{
    public function close(): void;
}
