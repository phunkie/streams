<?php

use Phunkie\Streams\IO\IO;
use function Phunkie\Streams\Functions\io\io;

describe("EffectfulStream", function () {

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