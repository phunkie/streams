<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Infinite;

/**
 * Interface for infinite stream sources (generators).
 *
 * Implementations produce an unbounded sequence of values via a Generator.
 */
interface Infinite
{
    /**
     * Get the values from this infinite source as a Generator.
     *
     * @return \Generator
     */
    public function getValues(): \Generator;

    /**
     * Reset the source to its initial state.
     */
    public function reset(): void;
}
