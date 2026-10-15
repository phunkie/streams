<?php

use function Phunkie\Streams\Functions\file\readAll;

use Phunkie\Streams\IO\File\Path;
use Phunkie\Streams\IO\Read;

describe("readAll", function () {

    beforeEach(function () {
        $this->file = sys_get_temp_dir() . '/read_all_' . uniqid() . '.txt';
        file_put_contents($this->file, "hello world");
    });

    afterEach(function () {
        unlink($this->file);
    });

    it("reads a Path in chunks through the file function", function () {
        expect(readAll(new Path($this->file), 5)->compile()->toArray())->toBe(["hello", " worl", "d"]);
    });

    it("takes a Path on Read as well", function () {
        expect(Stream(new Read(new Path($this->file)))->setBytes(6)->compile()->toArray())->toBe(["hello ", "world"]);
    });
});
