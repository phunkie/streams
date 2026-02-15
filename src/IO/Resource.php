<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\IO;

/**
 * Interface for pullable stream resources.
 *
 * Implementations provide a pull-based mechanism for reading data
 * from external sources (files, sockets, HTTP, etc.).
 */
interface Resource
{
    /** Sentinel value returned when a resource has reached end-of-stream. */
    public const EOF = 'Phunkie@Reserverd@Constant@EOF';
}
