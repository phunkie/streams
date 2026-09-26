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
    it("applies a map exactly once whether compiled to a list or an array", function () {
        $file = sys_get_temp_dir() . '/path_stream_' . uniqid() . '.txt';
        file_put_contents($file, "ab");

        try {
            $asList = Stream(new Path($file), 1)->map(fn ($chunk) => $chunk . '!')->compile()->toList();
            $asArray = Stream(new Path($file), 1)->map(fn ($chunk) => $chunk . '!')->compile()->toArray();

            expect($asList->toArray())->toBe(["a!", "b!"]);
            expect($asArray)->toBe(["a!", "b!"]);
        } finally {
            unlink($file);
        }
    });
});
