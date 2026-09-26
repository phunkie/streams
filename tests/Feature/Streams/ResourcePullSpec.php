<?php

use Phunkie\Streams\Pull\ResourcePull;
use Phunkie\Streams\Type\Stream;

describe("Stream from a resource handle", function () {

    beforeEach(function () {
        $this->file = sys_get_temp_dir() . '/resource_pull_' . uniqid() . '.txt';
        file_put_contents($this->file, "ab");
    });

    afterEach(function () {
        unlink($this->file);
    });

    it("applies a map exactly once whether compiled to a list or an array", function () {
        $asList = Stream::fromPull(new ResourcePull(fopen($this->file, 'rb'), 1))->map(fn ($chunk) => $chunk . '!')->compile()->toList();
        $asArray = Stream::fromPull(new ResourcePull(fopen($this->file, 'rb'), 1))->map(fn ($chunk) => $chunk . '!')->compile()->toArray();

        expect($asList->toArray())->toBe(["a!", "b!"]);
        expect($asArray)->toBe(["a!", "b!"]);
    });
});
