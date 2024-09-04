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
    function interleave(... $other): Pipeline
    {
        return new Pipeline(function($chunk) use ($other) {
            $pulls = array_merge([$chunk], array_map(fn($pull) => $pull->getValues(), $other));

            $heads = fn($matrix) => array_filter(array_map(fn($vector) => array_shift($vector), $matrix));
            $tails = fn($matrix) => array_filter(array_map(fn($vector) => array_slice($vector, 1), $matrix));
            $interleaved = fn($matrix, $self) =>
                count($matrix) ? array_merge($heads($matrix), $self($tails($matrix), $self)) : [];

            return $interleaved($pulls, $interleaved);
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
