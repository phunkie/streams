<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull\ResourcePull;

/**
 * Show operations for ResourcePull. Displays opaque representation since resource data is lazy.
 */
trait ShowOps
{
    /**
     * Return the type representation for resource-backed pulls.
     *
     * @return string
     */
    public function showType(): string
    {
        return 'Byte';
    }

    /**
     * Return an opaque string representation (resource content is not eagerly available).
     *
     * @return string
     */
    public function toString(): string
    {
        return '...';
    }
}
