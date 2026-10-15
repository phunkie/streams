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

use Phunkie\Effect\IO\IO;
use Phunkie\Types\ImmList;

/**
 * Contract for compiling a stream: into a collection, into its effects, into one folded value,
 * or into a generator for consumers outside the IO discipline.
 */
interface Compilable
{
    /**
     * Compile the stream into an ImmList, or an IO action for effectful streams.
     *
     * @return ImmList|IO
     */
    public function toList(): ImmList | IO;

    /**
     * Compile the stream into a plain PHP array.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Run the stream for its effects, one element at a time, discarding the output.
     *
     * @return IO<\Phunkie\Types\Unit>
     */
    public function drain(): IO;

    /**
     * Fold the output into one value, one element at a time, once the IO runs.
     *
     * @template B
     * @param B $initial
     * @param callable(B, mixed): B $f
     * @return IO<B>
     */
    public function fold(mixed $initial, callable $f): IO;

    /**
     * The output as a Generator, pulled and run as the consumer iterates.
     *
     * @return \Generator<int, mixed>
     */
    public function toGenerator(): \Generator;
}
