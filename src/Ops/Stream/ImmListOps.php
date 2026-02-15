<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Stream;

use Phunkie\Streams\Pull\ResourcePull;
use Phunkie\Streams\Pull\ResourcePullConcat;
use Phunkie\Streams\Pull\ValuesPull;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Stream;

/**
 * List-like operations for Stream. Provides concat, interleave, take, filter, and more.
 *
 * @method Pull getPull()
 * @method int getBytes()
 */
trait ImmListOps
{
    /**
     * Concatenate this stream with another stream of the same type.
     *
     * @param Stream $stream The stream to append
     * @return Stream
     * @throws \Error If mixing pure and non-pure streams
     */
    public function concat(Stream $stream): Stream
    {
        return match(get_class($this->getPull())) {
            ValuesPull::class => match (get_class($stream->getPull())) {
                ValuesPull::class => Stream(...array_merge($this->getPull()->getValues(), $stream->getPull()->getValues())),
                default => throw new \Error('Cannot concatenate pure with non pure streams')
            },
            ResourcePull::class => match (get_class($stream->getPull())) {
                ResourcePull::class => Stream(new ResourcePullConcat($this->getPull(), $stream->getPull())),
                default => throw new \Error('Cannot concatenate pure with non pure streams')
            }
        };
    }

    /**
     * Interleave elements from this stream with elements from other streams.
     *
     * @param Stream ...$streams Streams to interleave with
     * @return Stream
     */
    public function interleave(...$streams): Stream
    {
        $pulls = array_map(fn ($x) => $x->getPull(), $streams);

        return new Stream($this->getPull()->interleave(...$pulls), $this->getBytes());
    }

    /**
     * Take the first $n elements from the stream.
     *
     * @param int $n Number of elements to take
     * @return Stream
     */
    public function take(int $n): Stream
    {
        return new Stream($this->getPull()->take($n), $this->getBytes());
    }

    /**
     * Keep only elements that satisfy the predicate.
     *
     * @param callable $f A => bool
     * @return Stream
     */
    public function filter(callable $f): Stream
    {
        return new Stream($this->getPull()->filter($f), $this->getBytes());
    }

    /**
     * Pass this stream through a pipe function (Stream => Stream).
     *
     * @param callable $pipe Stream => Stream
     * @return Stream
     */
    public function through(callable $pipe): Stream
    {
        return $pipe($this);
    }

    /**
     * Take elements while the predicate holds true, then stop.
     *
     * @param callable $predicate A => bool
     * @return Stream
     */
    public function takeWhile(callable $predicate): Stream
    {
        return new Stream($this->getPull()->takeWhile($predicate), $this->getBytes());
    }

    /**
     * Drop elements while the predicate holds true, then emit the rest.
     *
     * @param callable $predicate A => bool
     * @return Stream
     */
    public function dropWhile(callable $predicate): Stream
    {
        return new Stream($this->getPull()->dropWhile($predicate), $this->getBytes());
    }

    /**
     * Group elements into chunks of the given size.
     *
     * @param int $size Number of elements per chunk
     * @return Stream
     */
    public function chunk(int $size): Stream
    {
        return new Stream($this->getPull()->chunk($size), $this->getBytes());
    }

    /**
     * Merge this stream with other streams by concatenating all values sequentially.
     *
     * @param Stream ...$streams Streams to merge
     * @return Stream
     */
    public function merge(Stream ...$streams): Stream
    {
        // Merge is similar to concat but combines all streams
        $allValues = $this->getPull()->getValues();
        foreach ($streams as $stream) {
            $allValues = array_merge($allValues, $stream->getPull()->getValues());
        }

        return Stream(...$allValues);
    }

    /**
     * Zip this stream with another, pairing corresponding elements as [a, b] arrays.
     * Stops at the length of the shorter stream.
     *
     * @param Stream $other The stream to zip with
     * @return Stream
     */
    public function zip(Stream $other): Stream
    {
        $thisValues = $this->getPull()->getValues();
        $otherValues = $other->getPull()->getValues();
        $zipped = [];
        $count = min(count($thisValues), count($otherValues));

        for ($i = 0; $i < $count; $i++) {
            $zipped[] = [$thisValues[$i], $otherValues[$i]];
        }

        return Stream(...$zipped);
    }
}
