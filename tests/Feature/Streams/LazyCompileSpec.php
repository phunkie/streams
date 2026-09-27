<?php

use function Phunkie\Effect\Functions\io\io;
use function Phunkie\Streams\Functions\file\writeFile;

use Phunkie\Streams\IO\File\Path;
use Phunkie\Streams\IO\Resource;

describe("Compiling emits as it pulls", function () {

    it("hands each row to the sink before fetching the next", function () {
        $log = [];
        $rows = [['n' => 1], ['n' => 2], ['n' => 3]];
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturnCallback(function () use (&$log, &$rows) {
            $row = array_shift($rows);
            $log[] = null === $row ? 'fetch end' : 'fetch ' . $row['n'];

            return $row ?? false;
        });

        StreamFromPDO($stmt)
            ->map(fn ($row) => $row['n'])
            ->evalTap(function ($n) use (&$log) {
                return io(function () use (&$log, $n) {
                    $log[] = "sink $n";
                });
            })
            ->compile()
            ->drain()
            ->unsafeRun();

        expect($log)->toBe(['fetch 1', 'sink 1', 'fetch 2', 'sink 2', 'fetch 3', 'sink 3', 'fetch end']);
    });

    it("stops fetching once take has enough", function () {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->exactly(2))->method('fetch')->willReturnOnConsecutiveCalls(['n' => 1], ['n' => 2], ['n' => 3], false);

        $taken = StreamFromPDO($stmt)->take(2)->compile()->toList();

        expect($taken->toArray())->toBe([['n' => 1], ['n' => 2]]);
    });

    it("stops fetching once takeWhile fails", function () {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->exactly(3))->method('fetch')->willReturnOnConsecutiveCalls(['n' => 1], ['n' => 2], ['n' => 3], ['n' => 4], false);

        $taken = StreamFromPDO($stmt)->map(fn ($row) => $row['n'])->takeWhile(fn ($n) => $n < 3)->compile()->toArray();

        expect($taken)->toBe([1, 2]);
    });

    it("keeps memory flat over two hundred thousand chunks", function () {
        $source = new class () implements Resource {
            private int $left = 200000;

            public function pull(int $chunkSize): mixed
            {
                if (0 === $this->left) {
                    return Resource::EOF;
                }
                $this->left--;

                return str_repeat('x', 100);
            }
        };
        $count = 0;
        $before = memory_get_peak_usage();

        Stream($source)
            ->map(fn (string $chunk) => strlen($chunk))
            ->evalTap(function () use (&$count) {
                return io(function () use (&$count) {
                    $count++;
                });
            })
            ->compile()
            ->drain()
            ->unsafeRun();

        expect($count)->toBe(200000);
        expect(memory_get_peak_usage() - $before)->toBeLessThan(8 * 1024 * 1024);
    });

    it("drains a file stream chunk by chunk", function () {
        $file = sys_get_temp_dir() . '/lazy_compile_' . uniqid() . '.txt';
        file_put_contents($file, "abcd");
        $seen = [];

        try {
            Stream(new Path($file), 2)
                ->evalTap(function ($chunk) use (&$seen) {
                    return io(function () use (&$seen, $chunk) {
                        $seen[] = $chunk;
                    });
                })
                ->compile()
                ->drain()
                ->unsafeRun();
        } finally {
            unlink($file);
        }

        expect($seen)->toBe(['ab', 'cd']);
    });

    it("filters, takes and evalMaps a file stream like any other", function () {
        $file = sys_get_temp_dir() . '/lazy_compile_' . uniqid() . '.txt';
        file_put_contents($file, "abcd");

        try {
            $chunks = Stream(new Path($file), 1)
                ->filter(fn ($chunk) => 'b' !== $chunk)
                ->evalMap(fn ($chunk) => io(fn () => strtoupper($chunk)))
                ->take(2)
                ->compile()
                ->toList()
                ->unsafeRun();
        } finally {
            unlink($file);
        }

        expect($chunks->toArray())->toBe(['A', 'C']);
    });

    it("writes a file stream through writeFile chunk by chunk", function () {
        $in = sys_get_temp_dir() . '/lazy_compile_in_' . uniqid() . '.txt';
        $out = sys_get_temp_dir() . '/lazy_compile_out_' . uniqid() . '.txt';
        file_put_contents($in, "abcd");

        try {
            Stream(new Path($in), 2)->map(fn ($chunk) => strtoupper($chunk))->through(writeFile(new Path($out)));

            expect(file_get_contents($out))->toBe("AB\nCD\n");
        } finally {
            unlink($in);
            unlink($out);
        }
    });

    it("flushes the last partial chunk once the input ends", function () {
        expect(Stream(1, 2, 3, 4, 5)->chunk(2)->compile()->toArray())->toBe([[1, 2], [3, 4], [5]]);
    });

    it("chunks then takes without pulling past what take needs", function () {
        $pulled = 0;
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturnCallback(function () use (&$pulled) {
            $pulled++;

            return $pulled > 10 ? false : ['n' => $pulled];
        });

        $chunks = StreamFromPDO($stmt)->map(fn ($row) => $row['n'])->chunk(2)->take(2)->compile()->toArray();

        expect($chunks)->toBe([[1, 2], [3, 4]]);
        expect($pulled)->toBe(4);
    });
});
