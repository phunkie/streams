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
 * Infinite stream that yields a constant value indefinitely.
 */
class Constant implements Infinite
{
    /**
     * @param mixed $pattern The value to yield on every iteration
     */
    public function __construct(private mixed $pattern)
    {
    }

    /** {@inheritdoc} */
    public function getValues(): \Generator
    {
        while (true) {
            yield $this->pattern;
        }
    }

    /** {@inheritdoc} */
    public function reset(): void
    {
        // do nothing
    }
}
