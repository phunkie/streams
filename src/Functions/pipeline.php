<?php

namespace Phunkie\Streams\Functions\pipeline {

    use Phunkie\Streams\IO\IO;
    use Phunkie\Streams\Type\Pipeline;

    const map = 'map';
    function map($f): Pipeline
    {
        return new Pipeline(fn($chunk) => array_map($f, $chunk));
    }

    const filter = 'filter';
    function filter(callable $f): Pipeline
    {
        return new Pipeline(fn($chunk) => array_filter($chunk, $f));
    }

    const interleave = 'interleave';
    function interleave(...$others): Pipeline
    {
        return new Pipeline(function ($chunk) use ($others) {
            $pulls = array_merge([$chunk], array_map(fn($pull) => $pull->getValues(), $others));

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
    function evalMap(callable $f): Pipeline
    {
        return new Pipeline(fn ($chunk) => ImmList(...array_map($f, $chunk)));
    }

    const evalFlatMap = 'evalFlatMap';
    function evalFlatMap(callable $f): Pipeline
    {
        return new Pipeline(fn ($chunk) => Stream(...array_map($f, $chunk)));
    }

    const evalFilter = 'evalFilter';
    function evalFilter(callable $f): Pipeline
    {
        return new Pipeline(fn ($chunk) => ImmList(...array_map(fn($x) => new IO(fn() =>$x),
            array_filter($chunk, fn($v) => $f($v)->run()))));
    }

    function evalTap($f): Pipeline
    {
        $pipeline = new Pipeline(
            function($chunk) use ($f) {
                foreach ($chunk as $v) {
                    $f($v)->run();
                }
                return $chunk;
            }
        );

        $pipeline->setPassthrough(true);
        return $pipeline;
    }

}
