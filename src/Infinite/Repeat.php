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
 * Infinite stream that cycles through a pattern of values repeatedly.
 *
 * Given values (a, b, c), yields a, b, c, a, b, c, ... indefinitely.
 */
class Repeat implements Infinite
{
    /** @var array The pattern of values to cycle through */
    private array $pattern;

    /**
     * @param mixed ...$pattern Values to cycle through
     */
    public function __construct(...$pattern)
    {
        $this->pattern = $pattern;
    }

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function reset(): void
    {
        reset($this->pattern);
    }
}
