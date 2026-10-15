<?php

use function Phunkie\Effect\Functions\io\io;

use Phunkie\Streams\IO\Resource;

describe("toGenerator", function () {

    it("yields the elements through the pipeline", function () {
        $generator = Stream(1, 2, 3)->map(fn ($x) => $x * 10)->compile()->toGenerator();

        expect($generator)->toBeInstanceOf(Generator::class);
        expect(iterator_to_array($generator, false))->toBe([10, 20, 30]);
    });

    it("pulls and runs effects as the consumer iterates", function () {
        $log = [];
        $source = new class ($log) implements Resource {
            private array $chunks = ['a', 'b'];

            public function __construct(private array &$log)
            {
            }

            public function pull(int $chunkSize): mixed
            {
                $chunk = array_shift($this->chunks);
                $this->log[] = null === $chunk ? 'eof' : "pull $chunk";

                return $chunk ?? Resource::EOF;
            }
        };
        $tapping = function ($chunk) use (&$log) {
            return io(function () use (&$log, $chunk) {
                $log[] = "tap $chunk";
            });
        };

        $generator = Stream($source)->evalTap($tapping)->compile()->toGenerator();
        expect($log)->toBe([]);

        $generator->current();
        $log[] = 'consumer has a';
        $generator->next();

        expect($log)->toBe(['pull a', 'tap a', 'consumer has a', 'pull b', 'tap b']);
    });

    it("stops pulling when take halts and flushes what chunk holds", function () {
        expect(iterator_to_array(Stream(1, 2, 3, 4, 5)->chunk(2)->take(2)->compile()->toGenerator(), false))->toBe([[1, 2], [3, 4]]);
        expect(iterator_to_array(Stream(1, 2, 3)->chunk(2)->compile()->toGenerator(), false))->toBe([[1, 2], [3]]);
    });
});
