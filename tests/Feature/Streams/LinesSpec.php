<?php

use Phunkie\Streams\IO\Resource;

describe("lines", function () {

    it("splits chunks into lines whatever the chunk boundaries", function () {
        $lines = Stream("ab\ncd", "e\nfg\n", "h")->lines()->compile()->toArray();

        expect($lines)->toBe(['ab', 'cde', 'fg', 'h']);
    });

    it("drops the carriage return of CRLF endings", function () {
        expect(Stream("a\r\nb\r\n")->lines()->compile()->toArray())->toBe(['a', 'b']);
    });

    it("emits no empty line for a trailing newline", function () {
        expect(Stream("a\nb\n")->lines()->compile()->toArray())->toBe(['a', 'b']);
    });

    it("keeps empty lines in the middle", function () {
        expect(Stream("a\n\nb")->lines()->compile()->toArray())->toBe(['a', '', 'b']);
    });

    it("emits each line as soon as its newline has been read", function () {
        $log = [];
        $source = new class ($log) implements Resource {
            private array $chunks = ["one\ntw", "o\nthree\n"];

            public function __construct(private array &$log)
            {
            }

            public function pull(int $chunkSize): mixed
            {
                $chunk = array_shift($this->chunks);
                $this->log[] = null === $chunk ? 'pull end' : 'pull ' . json_encode($chunk);

                return $chunk ?? Resource::EOF;
            }
        };

        Stream($source)
            ->lines()
            ->evalTap(function (string $line) use (&$log) {
                return \Phunkie\Effect\Functions\io\io(function () use (&$log, $line) {
                    $log[] = "line $line";
                });
            })
            ->compile()
            ->drain()
            ->unsafeRun();

        expect($log)->toBe(['pull "one\ntw"', 'line one', 'pull "o\nthree\n"', 'line two', 'line three', 'pull end']);
    });
});
