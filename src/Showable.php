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
 * Contract for types that can render themselves as human-readable strings.
 */
interface Showable
{
    /**
     * Return the value representation as a string.
     *
     * @return string
     */
    public function toString(): string;

    /**
     * Return a string describing this type (e.g. "Stream[IO, String]").
     *
     * @return string
     */
    public function showType(): string;
}
