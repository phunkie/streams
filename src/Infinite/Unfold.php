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
 * The Unfold class represents an infinite stream created by repeatedly applying
 * a function to a seed value, which produces both the current element and the next seed.
 */
class Unfold implements Infinite
{
    private $f;
    private $seed;
    private $originalSeed;
    private $values;

    /**
     * Create a new Unfold infinite stream.
     *
     * @param callable $f    Function that takes a seed and returns a Pair(currentValue, nextSeed)
     * @param mixed    $seed Initial seed value
     */
    public function __construct(callable $f, $seed)
    {
        $this->f = $f;
        $this->seed = $seed;
        $this->originalSeed = $seed;
        $this->values = $this->generate();
    }

    /**
     * Get the values from this infinite stream as a Generator.
     *
     * @return \Generator The generator yielding values from this infinite stream
     */
    public function getValues(): \Generator
    {
        return $this->values;
    }

    /** @return \Generator */
    private function generate(): \Generator
    {
        $currentSeed = $this->seed;
        while (true) {
            $result = ($this->f)($currentSeed);
            yield $result->_1;
            $currentSeed = $result->_2;
        }
    }

    /**
     * Reset the stream to its initial state.
     */
    public function reset(): void
    {
        $this->seed = $this->originalSeed;
    }

    /**
     * Get the seed value.
     *
     * @return mixed The seed value
     */
    public function getSeed(): mixed
    {
        return $this->seed;
    }

    /**
     * Get the unfold function.
     *
     * @return callable The unfold function
     */
    public function getUnfoldFn(): callable
    {
        return $this->f;
    }

    /**
     * Generate the next value in the infinite sequence.
     *
     * @param mixed $seed The current seed value
     * @return array [currentValue, nextSeed]
     */
    public function next($seed): array
    {
        $pair = ($this->f)($seed);

        return [$pair->_1, $pair->_2];
    }
}
