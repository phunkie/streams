<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\IO\File;

/**
 * Value object representing a filesystem path.
 */
class Path
{
    /**
     * @param string $pathname The filesystem path
     */
    public function __construct(private readonly string $pathname)
    {
    }

    /**
     * @return string The filesystem path as a string
     */
    public function toString(): string
    {
        return $this->pathname;
    }
}
