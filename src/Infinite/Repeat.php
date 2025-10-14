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

class Repeat implements Infinite
{
    private array $pattern;

    public function __construct(...$pattern)
    {
        $this->pattern = $pattern;
    }

    public function getValues(): \Generator
    {
        while (true) {
            if (key($this->pattern) === null) {
                reset($this->pattern);
            }
            yield current($this->pattern);
            next($this->pattern);
        }
    }

    public function reset(): void
    {
        reset($this->pattern);
    }
}
