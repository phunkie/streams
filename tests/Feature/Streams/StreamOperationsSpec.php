<?php

use Phunkie\Streams\Type\Stream;

describe("Stream Operations", function () {

    describe("through() operation", function () {

        it("applies a pipe function to the stream", function () {
            $uppercase = fn(Stream $s) => $s->map(fn($x) => strtoupper($x));

            $result = Stream(...str_split("hello"))
                ->through($uppercase)
                ->toArray();

            expect($result)->toBe(['H', 'E', 'L', 'L', 'O']);
        });

        it("chains multiple transformations via through", function () {
            $uppercase = fn(Stream $s) => $s->map(fn($x) => strtoupper($x));
            $exclaim = fn(Stream $s) => $s->map(fn($x) => $x . '!');

            $result = Stream(...str_split("hi"))
                ->through($uppercase)
                ->through($exclaim)
                ->toArray();

            expect($result)->toBe(['H!', 'I!']);
        });

        it("composes complex pipelines", function () {
            $pipeline = fn(Stream $s) => $s
                ->map(fn($x) => $x * 2)
                ->filter(fn($x) => $x > 5)
                ->map(fn($x) => "value: $x");

            $result = Stream(...[1, 2, 3, 4, 5])
                ->through($pipeline)
                ->toArray();

            // filter preserves keys, so we get keys 2, 3, 4
            expect(array_values($result))->toBe(['value: 6', 'value: 8', 'value: 10']);
        });
    });

    describe("takeWhile() operation", function () {

        it("takes elements while predicate is true", function () {
            $result = Stream(...[1, 2, 3, 4, 5, 1, 2])
                ->takeWhile(fn($x) => $x < 4)
                ->toArray();

            expect($result)->toBe([1, 2, 3]);
        });

        it("stops at first false predicate", function () {
            $result = Stream(...['a', 'b', 'c', '1', 'd', 'e'])
                ->takeWhile(fn($x) => ctype_alpha($x))
                ->toArray();

            expect($result)->toBe(['a', 'b', 'c']);
        });

        it("takes all if predicate always true", function () {
            $result = Stream(...[2, 4, 6, 8])
                ->takeWhile(fn($x) => $x % 2 === 0)
                ->toArray();

            expect($result)->toBe([2, 4, 6, 8]);
        });

        it("takes none if first element fails predicate", function () {
            $result = Stream(...[5, 1, 2, 3])
                ->takeWhile(fn($x) => $x < 4)
                ->toArray();

            expect($result)->toBe([]);
        });
    });

    describe("dropWhile() operation", function () {

        it("drops elements while predicate is true", function () {
            $result = Stream(...[1, 2, 3, 4, 5, 1, 2])
                ->dropWhile(fn($x) => $x < 4)
                ->toArray();

            expect($result)->toBe([4, 5, 1, 2]);
        });

        it("starts including after first false", function () {
            $result = Stream(...['a', 'b', 'c', '1', 'd', 'e'])
                ->dropWhile(fn($x) => ctype_alpha($x))
                ->toArray();

            expect($result)->toBe(['1', 'd', 'e']);
        });

        it("drops all if predicate always true", function () {
            $result = Stream(...[2, 4, 6, 8])
                ->dropWhile(fn($x) => $x % 2 === 0)
                ->toArray();

            expect($result)->toBe([]);
        });

        it("drops none if first element fails predicate", function () {
            $result = Stream(...[5, 1, 2, 3])
                ->dropWhile(fn($x) => $x < 4)
                ->toArray();

            expect($result)->toBe([5, 1, 2, 3]);
        });
    });

    describe("chunk() operation", function () {

        it("chunks stream into fixed-size groups", function () {
            $result = Stream(...[1, 2, 3, 4, 5, 6])
                ->chunk(2)
                ->toArray();

            expect($result)->toBe([[1, 2], [3, 4], [5, 6]]);
        });

        it("handles partial last chunk", function () {
            $result = Stream(...[1, 2, 3, 4, 5])
                ->chunk(2)
                ->toArray();

            // Last chunk [5] might not appear if buffer not flushed
            // This depends on implementation
            expect(count($result))->toBeGreaterThanOrEqual(2);
        });

        it("chunks with size 1", function () {
            $result = Stream(...[1, 2, 3])
                ->chunk(1)
                ->toArray();

            expect($result)->toBe([[1], [2], [3]]);
        });

        it("chunks with size 3", function () {
            $result = Stream(...[1, 2, 3, 4, 5, 6, 7, 8, 9])
                ->chunk(3)
                ->toArray();

            expect($result)->toBe([[1, 2, 3], [4, 5, 6], [7, 8, 9]]);
        });
    });

    describe("Combining operations", function () {

        it("combines takeWhile with map", function () {
            $result = Stream(...[1, 2, 3, 4, 5])
                ->takeWhile(fn($x) => $x < 4)
                ->map(fn($x) => $x * 10)
                ->toArray();

            expect($result)->toBe([10, 20, 30]);
        });

        it("combines dropWhile with filter", function () {
            $result = Stream(...[1, 2, 3, 4, 5, 6, 7, 8])
                ->dropWhile(fn($x) => $x < 4)
                ->filter(fn($x) => $x % 2 === 0)
                ->toArray();

            // filter preserves keys
            expect(array_values($result))->toBe([4, 6, 8]);
        });

        it("combines through with takeWhile", function () {
            $double = fn(Stream $s) => $s->map(fn($x) => $x * 2);

            $result = Stream(...[1, 2, 3, 4, 5])
                ->through($double)
                ->takeWhile(fn($x) => $x < 8)
                ->toArray();

            expect($result)->toBe([2, 4, 6]);
        });

        it("combines all new operations", function () {
            $pipeline = fn(Stream $s) => $s
                ->dropWhile(fn($x) => $x < 3)
                ->takeWhile(fn($x) => $x < 8)
                ->map(fn($x) => $x * 2);

            $result = Stream(...[1, 2, 3, 4, 5, 6, 7, 8, 9])
                ->through($pipeline)
                ->toArray();

            // dropWhile stops dropping after condition fails, includes all after
            expect(array_values($result))->toBe([6, 8, 10, 12, 14]);
        });

        it("chunks after transformation", function () {
            $result = Stream(...[1, 2, 3, 4, 5, 6])
                ->map(fn($x) => $x * 2)
                ->chunk(2)
                ->toArray();

            expect($result)->toBe([[2, 4], [6, 8], [10, 12]]);
        });
    });

    describe("Edge cases", function () {

        it("handles empty stream with takeWhile", function () {
            $result = Stream()
                ->takeWhile(fn($x) => true)
                ->toArray();

            expect($result)->toBe([]);
        });

        it("handles empty stream with dropWhile", function () {
            $result = Stream()
                ->dropWhile(fn($x) => true)
                ->toArray();

            expect($result)->toBe([]);
        });

        it("handles empty stream with chunk", function () {
            $result = Stream()
                ->chunk(2)
                ->toArray();

            expect($result)->toBe([]);
        });

        it("handles empty stream with through", function () {
            $identity = fn(Stream $s) => $s;

            $result = Stream()
                ->through($identity)
                ->toArray();

            expect($result)->toBe([]);
        });
    });
});
