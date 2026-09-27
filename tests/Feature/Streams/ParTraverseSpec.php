<?php

use function Phunkie\Effect\Functions\io\io;

use Phunkie\Effect\IO\IO;

describe("parTraverse", function () {

    it("runs the effects in batches and keeps the results in order", function () {
        $traversed = Stream(1, 2, 3, 4, 5)->parTraverse(2, fn ($n) => io(fn () => $n * 10));

        expect($traversed)->toBeInstanceOf(IO::class);
        expect($traversed->unsafeRunSync()->compile()->toArray())->toBe([10, 20, 30, 40, 50]);
    });

    it("traverses what the stream's transformations produce", function () {
        $traversed = Stream(1, 2, 3, 4)
            ->filter(fn ($n) => 0 === $n % 2)
            ->map(fn ($n) => $n + 1)
            ->parTraverse(3, fn ($n) => io(fn () => "n$n"))
            ->unsafeRunSync();

        expect($traversed->compile()->toArray())->toBe(['n3', 'n5']);
    });

    it("rejects a function that does not return an IO", function () {
        expect(fn () => Stream(1)->parTraverse(1, fn ($n) => $n)->unsafeRunSync())->toThrow(TypeError::class);
    });
});
