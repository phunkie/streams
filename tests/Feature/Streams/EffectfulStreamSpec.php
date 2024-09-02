<?php

use Phunkie\Streams\IO\IO;
use function Phunkie\Streams\Functions\io\io;

describe("EffectfulStream", function () {

    describe("evalMap", function () {
        beforeEach(function() {
            $this->stream = Stream("London", "Paris", "Amsterdam")
                ->evalMap(fn($x) => io(fn() => strtoupper($x)));
        });

        it("implements evalMap", function () {
            expect($this->stream->compile->toList())
                ->toBeInstanceOf(IO::class);
        });

        it("can be unsafely ran", function () {
            expect($this->stream
                ->compile
                ->toList()
                ->unsafeRunSync())
                ->toEqual(ImmList("LONDON", "PARIS", "AMSTERDAM"));
        });
    });

    describe("evalFilter", function () {
        beforeEach(function () {
            $this->stream = Stream("London", "Paris", "Amsterdam")
                ->evalFilter(fn($x) => io(fn() => strlen($x) > 5));
        });

        it("implements evalFilter", function () {
            expect($this->stream->compile->toList())
                ->toBeInstanceOf(IO::class);
        });

        it("can be unsafely ran", function () {
            expect($this->stream
                ->compile
                ->toList()
                ->unsafeRunSync())
                ->toEqual(ImmList("London", "Amsterdam"));
        });

    });

    describe("evalTap", function () {
        beforeEach(function () {
            $this->stream = Stream("London", "Paris", "Amsterdam")
                ->evalTap(fn($x) => io(fn() => strlen($x) > 5));
        });

        it("implements evalTap", function () {
            expect($this->stream->compile->toList())
                ->toBeInstanceOf(IO::class);
        });

        it("does not change the stream", function () {
            expect($this->stream
                ->compile
                ->toList()
                ->unsafeRunSync())
                ->toEqual(["London", "Paris", "Amsterdam"]);
        });

        it("return IO<Unit> when drained", function () {
            expect($this->stream
                ->compile
                ->drain)
                ->toEqual(new IO(fn() => Unit()));
        });
    });

});
