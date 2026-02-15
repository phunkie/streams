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
 * Infinite stream that repeatedly applies a function to a seed value.
 *
 * Produces the sequence: seed, f(seed), f(f(seed)), ...
 */
class Iterate implements Infinite
{
    private \Generator $values;

    /**
     * @param \Closure|string $f     Function applied to produce the next value
     * @param int             $start Initial seed value
     */
    public function __construct(private readonly \Closure|string $f, private readonly int $start)
    {
        $this->values = $this->generate();
    }

    /** {@inheritdoc} */
    public function getValues(): \Generator
    {
        return $this->values;
    }

    /**
     * @return \Generator Yields seed, f(seed), f(f(seed)), ...
     */
    private function generate(): \Generator
    {
        for ($i = $this->start; true; $i = ($this->f)($i)) {
            yield $i;
        }
    }

    /** {@inheritdoc} */
    public function reset(): void
    {
        $this->values = $this->generate();
    }
}
