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

    it('We can call repeat on a finite stream to create an infinite stream', function () {
        expect(
            Stream(1, 2, 3)
                ->repeat
                ->take(15)
                ->compile
                ->toList()
        )->toEqual(ImmList(1, 2, 3, 1, 2, 3, 1, 2, 3, 1, 2, 3, 1, 2, 3));
    });
});