<?php

use function Phunkie\Effect\Functions\io\io;

use Phunkie\Streams\Closeable;
use Phunkie\Streams\IO\Resource;
use Phunkie\Streams\Pull\ResourcePull;
use Phunkie\Streams\Type\Stream;

function closingSource(array &$log, array $chunks): Resource
{
    return new class ($log, $chunks) implements Resource, Closeable {
        public function __construct(private array &$log, private array $chunks)
        {
        }

        public function pull(int $chunkSize): mixed
        {
            $chunk = array_shift($this->chunks);
            $this->log[] = null === $chunk ? 'eof' : "pull $chunk";

            return $chunk ?? Resource::EOF;
        }

        public function close(): void
        {
            $this->log[] = 'close';
        }
    };
}

describe("Closing", function () {

    it("closes the source once when the stream ends", function () {
        $log = [];

        Stream(closingSource($log, ['a', 'b']))->compile()->drain()->unsafeRun();

        expect($log)->toBe(['pull a', 'pull b', 'eof', 'close']);
    });

    it("closes the source when take halts the stream early", function () {
        $log = [];

        Stream(closingSource($log, ['a', 'b', 'c']))->take(1)->compile()->toList();

        expect($log)->toBe(['pull a', 'close']);
    });

    it("closes the source when an effect throws", function () {
        $log = [];
        $stream = Stream(closingSource($log, ['a', 'b']))->evalTap(fn () => io(fn () => throw new RuntimeException('boom')));

        expect(fn () => $stream->compile()->drain()->unsafeRun())->toThrow(RuntimeException::class);

        expect($log)->toBe(['pull a', 'close']);
    });

    it("closes the source when a generator is abandoned", function () {
        $log = [];
        $generator = Stream(closingSource($log, ['a', 'b', 'c']))->compile()->toGenerator();

        expect($generator->current())->toBe('a');
        unset($generator);

        expect($log)->toBe(['pull a', 'close']);
    });

    it("closes a PDO statement's cursor after take", function () {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturnOnConsecutiveCalls(['n' => 1], ['n' => 2], ['n' => 3], false);
        $stmt->expects($this->once())->method('closeCursor');

        StreamFromPDO($stmt)->take(1)->compile()->toArray();
    });

    it("closes a file handle after take and reads nothing from it afterwards", function () {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, 'abcd');
        rewind($handle);
        $pull = new ResourcePull($handle, 1);

        $first = Stream::fromPull($pull)->take(1)->compile()->toArray();

        expect($first)->toBe(['a']);
        expect(is_resource($handle))->toBeFalse();
        expect(Stream::fromPull($pull)->compile()->toArray())->toBe([]);
    });
});
