<?php

use Phunkie\Streams\IO\File\Path;

describe("Stream from a Path", function () {

    it("reads the file in chunks of the requested size", function () {
        $file = sys_get_temp_dir() . '/path_stream_' . uniqid() . '.txt';
        file_put_contents($file, "hello world");

        try {
            $chunks = Stream(new Path($file), 5)->compile()->toList();

            expect($chunks->toArray())->toBe(["hello", " worl", "d"]);
        } finally {
            unlink($file);
        }
    });

    it("maps over the chunks", function () {
        $file = sys_get_temp_dir() . '/path_stream_' . uniqid() . '.txt';
        file_put_contents($file, "ab");

        try {
            $chunks = Stream(new Path($file), 1)->map(fn ($chunk) => strtoupper($chunk))->compile()->toList();

            expect($chunks->toArray())->toBe(["A", "B"]);
        } finally {
            unlink($file);
        }
    });
});
