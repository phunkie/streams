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

class Constant implements Infinite
{
    public function __construct(private mixed $pattern)
    {
    }

    public function getValues(): \Generator
    {
        while (true) {
            yield $this->pattern;
        }
    }

    public function reset(): void
    {
        // do nothing
    }
}
