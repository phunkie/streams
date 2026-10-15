<?php

use function Phunkie\Effect\Functions\io\io;

describe("take on an infinite source", function () {

    it("takes the first n values", function () {
        expect(Stream(fromRange(1))->take(5)->compile->toArray())->toBe([1, 2, 3, 4, 5]);
    });

    it("composes with the operations that follow it", function () {
        $list = Stream(iterate(1)(fn ($x) => $x * 2))->take(3)->map(fn ($x) => $x + 1)->compile->toList();

        expect($list->toArray())->toBe([2, 3, 5]);
    });

    it("pulls one value at a time instead of materialising the n values first", function () {
        $count = 0;
        $counting = function () use (&$count) {
            return io(function () use (&$count) {
                $count++;
            });
        };
        $before = memory_get_peak_usage();

        Stream(fromRange(1))->take(300000)->evalTap($counting)->compile()->drain()->unsafeRun();

        expect($count)->toBe(300000);
        expect(memory_get_peak_usage() - $before)->toBeLessThan(8 * 1024 * 1024);
    });
});
