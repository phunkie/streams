<?php

use const Phunkie\Functions\numbers\increment;

describe('creates infinite streams', function () {

    it('can be created from a range', function () {
        expect(Stream(fromRange(0, 100000000))->take(10)->compile->toArray())
            ->toBe([0, 1, 2, 3, 4, 5, 6, 7, 8, 9]);
    });

    it('can be created from an iterate', function () {
        expect(Stream(iterate(0)(increment))->take(10)->compile->toArray())
        ->toBe([0, 1, 2, 3, 4, 5, 6, 7, 8, 9]);
    });

    it('can be created from a constant', function () {
        expect(Stream(fromConstant("Yes"))->take(10)->compile->toArray())
            ->toBe(["Yes", "Yes", "Yes", "Yes", "Yes", "Yes", "Yes", "Yes", "Yes", "Yes"]);
    });

    it('can be created from unfold', function () {
        // Generate Fibonacci sequence: 0, 1, 1, 2, 3, 5, 8, 13, 21, 34
        $fibonacciUnfold = unfold(Pair(0, 1))(
            fn ($p) => Pair($p->_1, Pair($p->_2, $p->_1 + $p->_2))
        );

        expect(Stream($fibonacciUnfold)->take(10)->compile->toArray())
            ->toBe([0, 1, 1, 2, 3, 5, 8, 13, 21, 34]);
    });

    it('demonstrates unfold vs iterate with powers of 2', function () {
        // Using iterate to generate powers of 2
        $powersOfTwoIterate = Stream(iterate(1)(fn ($x) => $x * 2))
            ->take(10)
            ->compile
            ->toArray();

        // Using unfold to generate powers of 2
        $powersOfTwoUnfold = Stream(unfold(1)(
            fn ($x) => Pair($x, $x * 2)
        ))
            ->take(10)
            ->compile
            ->toArray();

        expect($powersOfTwoIterate)->toBe([1, 2, 4, 8, 16, 32, 64, 128, 256, 512]);
        expect($powersOfTwoUnfold)->toBe([1, 2, 4, 8, 16, 32, 64, 128, 256, 512]);
    });

    it('We can call repeat on a finite stream to create an infinite stream', function () {
        expect(
            Stream(1, 2, 3)
                ->repeat
                ->take(15)
                ->compile
                ->toList()
        )->toEqual(ImmList(1, 2, 3, 1, 2, 3, 1, 2, 3, 1, 2, 3, 1, 2, 3));
    });

    it('implements awakeEvery', function () {
        expect(Stream(awakeEvery(0.000001))->take(2)->compile->toList()->unsafeRunSync()->toArray())->toHaveCount(2);
    });
});
