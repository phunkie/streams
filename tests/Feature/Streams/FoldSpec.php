<?php

use function Phunkie\Effect\Functions\io\io;

use Phunkie\Effect\IO\IO;
use Phunkie\Streams\IO\File\Path;

describe("foldLeft", function () {

    it("folds the elements into an IO of the result, partially applied like ImmList", function () {
        $sum = Stream(1, 2, 3, 4)->foldLeft(0)(fn (int $acc, int $x) => $acc + $x);

        expect($sum)->toBeInstanceOf(IO::class);
        expect($sum->unsafeRun())->toBe(10);
    });

    it("runs the pipeline only when the IO runs", function () {
        $runs = 0;
        $counting = function ($x) use (&$runs) {
            return io(function () use (&$runs) {
                $runs++;
            });
        };

        $count = Stream('a', 'b')->evalTap($counting)->foldLeft(0)(fn (int $n) => $n + 1);

        expect($runs)->toBe(0);
        expect($count->unsafeRun())->toBe(2);
        expect($runs)->toBe(2);
    });

    it("folds a resource stream one chunk at a time", function () {
        $file = sys_get_temp_dir() . '/fold_' . uniqid() . '.txt';
        file_put_contents($file, "one\ntwo\nthree\n");

        try {
            $lines = Stream(new Path($file), 4)->lines()->foldLeft(0)(fn (int $n, string $line) => $n + 1)->unsafeRun();
        } finally {
            unlink($file);
        }

        expect($lines)->toBe(3);
    });

    it("is also available on the compiler", function () {
        expect(Stream(1, 2, 3)->compile()->fold('', fn (string $acc, int $x) => $acc . $x)->unsafeRun())->toBe('123');
    });
});
