<?php

use Phunkie\Effect\IO\IO;

describe("Compiler", function () {

    it("drains as a method call as well as a property", function () {
        $asMethod = Stream(1, 2, 3)->compile()->drain();
        $asProperty = Stream(1, 2, 3)->compile()->drain;

        expect($asMethod)->toBeInstanceOf(IO::class);
        expect($asProperty)->toBeInstanceOf(IO::class);
        expect($asMethod->unsafeRun())->toEqual(Unit());
    });
});
