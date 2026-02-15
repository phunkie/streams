<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Functions\transformation {

    use Phunkie\Streams\Type\Transformation;

    const map = 'map';

    /**
     * Apply a function to each element in the stream.
     *
     * @param callable $f The mapping function
     * @return Transformation
     */
    function map($f): Transformation
    {
        return new Transformation(fn ($chunk) => array_map($f, $chunk));
    }

    const filter = 'filter';

    /**
     * Keep only elements that satisfy the predicate.
     *
     * @param callable $f The predicate function
     * @return Transformation
     */
    function filter(callable $f): Transformation
    {
        return new Transformation(fn ($chunk) => array_filter($chunk, $f));
    }

    const flatMap = 'flatMap';

    /**
     * Map each element to a Stream or array and flatten the results.
     *
     * @param callable $f Function returning a Stream, array, or scalar per element
     * @return Transformation
     */
    function flatMap(callable $f): Transformation
    {
        return new Transformation(function ($chunk) use ($f) {
            $result = [];
            foreach ($chunk as $value) {
                $stream = $f($value);
                if ($stream instanceof \Phunkie\Streams\Type\Stream) {
                    $elements = $stream->compile()->toArray();
                    $result = array_merge($result, $elements);
                } elseif (is_array($stream)) {
                    $result = array_merge($result, $stream);
                } else {
                    $result[] = $stream;
                }
            }

            return $result;
        });
    }

    const flatten = 'flatten';

    /**
     * Flatten one level of nested Streams or arrays.
     *
     * @return Transformation
     */
    function flatten(): Transformation
    {
        return new Transformation(function ($chunk) {
            $result = [];
            foreach ($chunk as $value) {
                if ($value instanceof \Phunkie\Streams\Type\Stream) {
                    $elements = $value->compile()->toArray();
                    $result = array_merge($result, $elements);
                } elseif (is_array($value)) {
                    $result = array_merge($result, $value);
                } else {
                    $result[] = $value;
                }
            }

            return $result;
        });
    }

    const interleave = 'interleave';

    /**
     * Interleave elements from this stream with one or more other streams, alternating round-robin.
     *
     * @param \Phunkie\Streams\Type\Stream ...$others Streams to interleave with
     * @return Transformation
     */
    function interleave(...$others): Transformation
    {
        return new Transformation(function ($chunk) use ($others) {
            $pulls = array_merge([$chunk], array_map(fn ($pull) => $pull->getValues(), $others));

            $indices = array_fill(0, count($pulls), 0);

            $interleaved = [];
            $totalElements = array_sum(array_map('count', $pulls));
            $currentElement = 0;

            while ($currentElement < $totalElements) {
                foreach ($pulls as $i => &$pull) {
                    if (isset($pull[$indices[$i]])) {
                        $interleaved[] = $pull[$indices[$i]];
                        $indices[$i]++;
                        $currentElement++;
                    }
                }
            }

            return $interleaved;
        });
    }

    const evalMap = 'evalMap';

    /**
     * Map each element through an effectful function (returning IO) and run the effect.
     *
     * @param callable $f Function returning an IO per element
     * @return Transformation
     */
    function evalMap(callable $f): Transformation
    {
        return new Transformation(fn ($chunk) => ImmList(...array_map(fn ($x) => $f($x)->unsafeRun(), $chunk)));
    }

    const evalFlatMap = 'evalFlatMap';

    /**
     * Map each element through a function and flatten the resulting Streams.
     *
     * @param callable $f Function returning a value to be wrapped in a Stream per element
     * @return Transformation
     */
    function evalFlatMap(callable $f): Transformation
    {
        return new Transformation(fn ($chunk) => Stream(...array_map($f, $chunk)));
    }

    const evalFilter = 'evalFilter';

    /**
     * Filter elements using an effectful predicate (returning IO<bool>) and run the effect.
     *
     * @param callable $f Predicate function returning an IO<bool> per element
     * @return Transformation
     */
    function evalFilter(callable $f): Transformation
    {
        return new Transformation(fn ($chunk) => ImmList(...array_filter($chunk, fn ($v) => $f($v)->unsafeRun())));
    }

    /**
     * Run an effectful function (returning IO) on each element for its side effect, passing elements through unchanged.
     *
     * @param callable $f Function returning an IO per element (result is discarded)
     * @return Transformation
     */
    function evalTap($f): Transformation
    {
        $transformation = new Transformation(
            function ($chunk) use ($f) {
                foreach ($chunk as $v) {
                    $f($v)->unsafeRun();
                }

                return $chunk;
            }
        );

        $transformation->setPassthrough(true);

        return $transformation;
    }

    const takeWhile = 'takeWhile';

    /**
     * Emit elements while the predicate holds, then stop.
     *
     * @param callable $predicate The predicate to test each element
     * @return Transformation
     */
    function takeWhile(callable $predicate): Transformation
    {
        return new Transformation(function ($chunk) use ($predicate) {
            $result = [];
            foreach ($chunk as $value) {
                if (!$predicate($value)) {
                    break;
                }
                $result[] = $value;
            }

            return $result;
        });
    }

    const dropWhile = 'dropWhile';

    /**
     * Skip elements while the predicate holds, then emit the rest.
     *
     * @param callable $predicate The predicate to test each element
     * @return Transformation
     */
    function dropWhile(callable $predicate): Transformation
    {
        $dropping = true;

        return new Transformation(function ($chunk) use ($predicate, &$dropping) {
            $result = [];
            foreach ($chunk as $value) {
                if ($dropping && !$predicate($value)) {
                    $dropping = false;
                }
                if (!$dropping) {
                    $result[] = $value;
                }
            }

            return array_values($result);
        });
    }

    const chunk = 'chunk';

    /**
     * Group elements into fixed-size sub-arrays (chunks). The last chunk may be smaller.
     *
     * @param int $size Number of elements per chunk
     * @return Transformation
     */
    function chunk(int $size): Transformation
    {
        $buffer = [];

        return new Transformation(function ($chunk) use ($size, &$buffer) {
            $chunks = [];
            foreach ($chunk as $value) {
                $buffer[] = $value;
                if (count($buffer) === $size) {
                    $chunks[] = $buffer;
                    $buffer = [];
                }
            }
            // Flush remaining buffer as final chunk
            if (!empty($buffer)) {
                $chunks[] = $buffer;
                $buffer = [];
            }

            return $chunks;
        });
    }
}
