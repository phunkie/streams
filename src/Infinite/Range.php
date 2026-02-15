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
 * Generates a numeric range from start (inclusive) to end (exclusive).
 *
 * Can represent very large ranges (up to PHP_INT_MAX) without
 * allocating memory for all values at once.
 */
final class Range implements Infinite
{
    private \Generator $values;

    /**
     * @param int $start Start of the range (inclusive)
     * @param int $end   End of the range (exclusive)
     * @param int $step  Step increment between values
     */
    public function __construct(private int $start, private int $end, private int $step = 1)
    {
        $this->values = $this->generate();
    }

    /**
     * @return \Generator Yields integers from start to end-1 by step
     */
    private function generate(): \Generator
    {
        for ($i = $this->start; $i < $this->end; $i += $this->step) {
            yield $i;
        }
    }

    /** {@inheritdoc} */
    public function getValues(): \Generator
    {
        return $this->values;
    }

    /** {@inheritdoc} */
    public function reset(): void
    {
        $this->values = $this->generate();
    }
}
