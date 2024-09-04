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
            $interleaved = [];
            $pulls = [];
            $pulls[] = $chunk;

            $pulls = array_merge($pulls, array_map(fn($x) => $x->getValues(), $other));

            for ($p = $pulls; count($p); $p = array_filter($p)) {

                foreach ($p as &$pull) {
                    $interleaved[] = array_shift($pull);
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
