<?php

use function Phunkie\Effect\Functions\io\io;
use function Phunkie\Streams\Functions\io\readHandle;

describe("readHandle", function () {

    beforeEach(function () {
        $this->file = sys_get_temp_dir() . '/read_handle_' . uniqid() . '.txt';
        file_put_contents($this->file, 'abcdef');
    });

    afterEach(function () {
        unlink($this->file);
    });

    it("opens on the first pull, not when the stream is built", function () {
        $opened = 0;
        $open = io(function () use (&$opened) {
            $opened++;

            return fopen($this->file, 'rb');
        });

        $stream = readHandle($open, 4);
        expect($opened)->toBe(0);

        expect($stream->compile()->toArray())->toBe(['abcd', 'ef']);
        expect($opened)->toBe(1);
    });

    it("closes the handle when the stream ends", function () {
        $handle = null;
        $open = io(function () use (&$handle) {
            return $handle = fopen($this->file, 'rb');
        });

        readHandle($open, 2)->compile()->drain()->unsafeRun();

        expect(is_resource($handle))->toBeFalse();
    });

    it("closes the handle when take cuts the stream short", function () {
        $handle = null;
        $open = io(function () use (&$handle) {
            return $handle = fopen($this->file, 'rb');
        });

        expect(readHandle($open, 2)->take(1)->compile()->toArray())->toBe(['ab']);
        expect(is_resource($handle))->toBeFalse();
    });

    it("refuses an IO that does not open a stream", function () {
        expect(fn () => readHandle(io(fn () => 'not a handle'))->compile()->toArray())->toThrow(InvalidArgumentException::class);
    });
});
