<?php

use const Phunkie\Functions\numbers\increment;

it('creates pure streams', function () {
    expect(Stream(1, 2, 3)->showType())->toBe('Stream<Pure, Int>',)
        ->and(Stream(1, 2, 3)->toString())->toBe('Stream(1, 2, 3)');
});

it('can be compiled into another structure', function () {
    expect(Stream(1, 2, 3)->compile->toList())->toEqual(ImmList(1, 2, 3))
        ->and(Stream(1, 2, 3)->compile->toArray())->toBe([1, 2, 3]);
});

describe('streams are a functor', function () {

    it('implements map', function () {
        expect(Stream(1, 2, 3, 4)->map(increment)->compile->toList())->toEqual(ImmList(2, 3, 4, 5));
    });

    it('implements void', function () {
        expect(Stream(1, 2, 3, 4)->void()->compile->toList())->toEqual(ImmList(Unit(), Unit(), Unit(), Unit()));
    });

    it('implements as', function () {
        expect(Stream(1, 2, 3, 4)->as(1)->compile->toList())->toEqual(ImmList(1, 1, 1, 1));
    });

    it('implements zipWith', function () {
        expect(Stream(1, 2, 3, 4)->zipWith(increment)->compile->toList())->toEqual(
            ImmList(Pair(1, 2), Pair(2, 3), Pair(3, 4), Pair(4, 5))
        );
    });
});

describe("stream has list operations", function () {

    it('implements concat', function () {
        expect(Stream(1, 2, 3, 4)->concat(Stream(5, 6, 7, 8))->compile->toList())
            ->toEqual(ImmList(1, 2, 3, 4, 5, 6, 7, 8));
    });

    it('implements take', function () {
        expect(Stream(1, 2, 3, 4)->take(2)->compile->toList())
            ->toEqual(ImmList(1, 2));
    });

    it('implements filter', function () {
        expect(Stream(1, 2, 3, 4)->filter(fn($x) => $x > 2)->compile->toList())
            ->toEqual(ImmList(3, 4));
    });
});
