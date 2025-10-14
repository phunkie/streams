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

class Iterate implements Infinite
{
    private \Generator $values;

    public function __construct(private readonly \Closure|string $f, private readonly int $start)
    {
        $this->values = $this->generate();
    }

    public function getValues(): \Generator
    {
        return $this->values;
    }

    private function generate(): \Generator
    {
        for ($i = $this->start; true; $i = ($this->f)($i)) {
            yield $i;
        }
    }

    public function reset(): void
    {
        $this->values = $this->generate();
    }
}
