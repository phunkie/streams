<?php

use Phunkie\Streams\IO\File\Path;
use Phunkie\Streams\IO\Read;

describe("Stream chunk size", function () {

    beforeEach(function () {
        $this->file = sys_get_temp_dir() . '/chunk_size_' . uniqid() . '.txt';
        file_put_contents($this->file, "hello world");
    });

    afterEach(function () {
        unlink($this->file);
    });

    it("reads the file in chunks of the size given to setBytes", function () {
        $chunks = Stream(new Path($this->file))->setBytes(5)->compile()->toList();

        expect($chunks->toArray())->toBe(["hello", " worl", "d"]);
    });

    it("reads the file in chunks of the size given to readAll", function () {
        $chunks = Read::readAll($this->file, 5)->compile()->toList();

        expect($chunks->toArray())->toBe(["hello", " worl", "d"]);
    });

    it("applies the size given to setBytes after a map", function () {
        $chunks = Stream(new Path($this->file))
            ->map(fn ($chunk) => strtoupper($chunk))
            ->setBytes(5)
            ->compile()
            ->toList();

        expect($chunks->toArray())->toBe(["HELLO", " WORL", "D"]);
    });

    it("leaves a pure stream untouched", function () {
        $values = Stream(1, 2, 3)->setBytes(5)->compile()->toList();

        expect($values->toArray())->toBe([1, 2, 3]);
    });
});
