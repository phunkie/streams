<?php

use function Phunkie\Effect\Functions\io\io;

use Phunkie\Effect\IO\IO;

describe("Effect composition", function () {

    it("runs evalTap effects once per element when compiled to a list", function () {
        $runs = 0;

        $list = Stream(1, 2)
            ->evalTap(function ($x) use (&$runs) {
                return io(function () use (&$runs) {
                    $runs++;
                });
            })
            ->compile()
            ->toList();

        expect($list->toArray())->toBe([1, 2]);
        expect($runs)->toBe(2);
    });

    it("compiles an evalTap stream to an array", function () {
        $array = Stream(1, 2)->evalTap(fn ($x) => io(fn () => null))->compile()->toArray();

        expect($array)->toBe([1, 2]);
    });

    it("runs effects registered after a map when drained", function () {
        $seen = [];

        Stream(1, 2)
            ->map(fn ($x) => $x * 2)
            ->evalTap(function ($x) use (&$seen) {
                return io(function () use (&$seen, $x) {
                    $seen[] = $x;
                });
            })
            ->compile()
            ->drain()
            ->unsafeRun();

        expect($seen)->toBe([2, 4]);
    });

    it("maps after an evalTap", function () {
        $seen = [];

        $list = Stream(1, 2)
            ->evalTap(function ($x) use (&$seen) {
                return io(function () use (&$seen, $x) {
                    $seen[] = $x;
                });
            })
            ->map(fn ($x) => $x * 2)
            ->compile()
            ->toList();

        expect($list->toArray())->toBe([2, 4]);
        expect($seen)->toBe([1, 2]);
    });

    it("keeps evalMap lazy after a map", function () {
        $runs = 0;

        $result = Stream(1, 2)
            ->map(fn ($x) => $x * 2)
            ->evalMap(function ($x) use (&$runs) {
                return io(function () use (&$runs, $x) {
                    $runs++;

                    return $x + 1;
                });
            })
            ->compile()
            ->toList();

        expect($result)->toBeInstanceOf(IO::class);
        expect($runs)->toBe(0);
        expect($result->unsafeRunSync()->toArray())->toBe([3, 5]);
        expect($runs)->toBe(2);
    });

    it("runs evalMap effects when drained", function () {
        $runs = 0;

        Stream(1, 2)
            ->evalMap(function ($x) use (&$runs) {
                return io(function () use (&$runs, $x) {
                    $runs++;

                    return $x;
                });
            })
            ->compile()
            ->drain()
            ->unsafeRun();

        expect($runs)->toBe(2);
    });
});
