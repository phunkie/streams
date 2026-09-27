<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Type;

use Phunkie\Streams\Compilable;
use Phunkie\Streams\Showable;

/**
 * Pull-based stream interface that lazily produces values on demand.
 *
 * Combines Iterator for traversal, Showable for display, and Compilable
 * for materialising results. Concrete pulls (values, resources, infinite)
 * implement the actual data-sourcing logic; elements() is how the compile
 * path reads them, one at a time.
 *
 * @method static map(callable $f)
 * @method static flatMap(callable $f)
 * @method static flatten()
 * @method static evalMap(callable $f)
 * @method static evalTap(callable $f)
 * @method static evalFilter(callable $f)
 * @method static evalFlatMap(callable $f)
 * @method static interleave(Pull ...$pulls)
 * @method static take(int $n)
 * @method static filter(callable $f)
 * @method static takeWhile(callable $predicate)
 * @method static dropWhile(callable $predicate)
 * @method static chunk(int $size)
 * @method static lines()
 * @method array getValues()
 * @method Scope getScope()
 * @method void setScope(Scope $scope)
 */
interface Pull extends Showable, Compilable, \Iterator
{
    /**
     * Pull the next value from the underlying source.
     *
     * @return mixed
     */
    public function pull(): mixed;

    /**
     * Yield the source's elements one at a time, each pulled only when the consumer asks for it.
     *
     * @return \Generator<int, mixed>
     */
    public function elements(): \Generator;

    /**
     * Lift this Pull back into a Stream.
     *
     * @return Stream
     */
    public function toStream(): Stream;

    /**
     * Set the number of bytes read on each pull. Pulls that do not read in chunks ignore it.
     *
     * @param int $chunkSize Bytes per pull.
     * @return static
     */
    public function setChunkSize(int $chunkSize): static;
}
