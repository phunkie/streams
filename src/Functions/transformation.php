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
    function map($f): Transformation
    {
        return new Transformation(fn ($chunk) => array_map($f, $chunk));
    }

    const filter = 'filter';
    function filter(callable $f): Transformation
    {
        return new Transformation(fn ($chunk) => array_filter($chunk, $f));
    }

    const flatMap = 'flatMap';
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
    function evalMap(callable $f): Transformation
    {
        return new Transformation(fn ($chunk) => ImmList(...array_map(fn ($x) => $f($x)->unsafeRun(), $chunk)));
    }

    const evalFlatMap = 'evalFlatMap';
    function evalFlatMap(callable $f): Transformation
    {
        return new Transformation(fn ($chunk) => Stream(...array_map($f, $chunk)));
    }

    const evalFilter = 'evalFilter';
    function evalFilter(callable $f): Transformation
    {
        return new Transformation(fn ($chunk) => ImmList(...array_filter($chunk, fn ($v) => $f($v)->unsafeRun())));
    }

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
