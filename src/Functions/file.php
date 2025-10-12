<?php

namespace Phunkie\Streams\IO\File {

    use Phunkie\Effect\IO\IO;
    use Phunkie\Streams\IO\File\Path;
    use Phunkie\Streams\IO\Read;
    use Phunkie\Streams\Type\Stream;
    use function Phunkie\Effect\Functions\io\io;
    use function Phunkie\Streams\Functions\resource\bracket;

    function readAll($path, $chunk): Stream
    {
        return Read::readAll($path, $chunk);
    }

    /**
     * Check if a file exists
     *
     * @param Path $path
     * @return IO<bool>
     */
    function exists(Path $path): IO
    {
        return io(fn() => file_exists($path->toString()));
    }

    /**
     * Delete a file
     *
     * @param Path $path
     * @return IO<bool>
     */
    function deleteFile(Path $path): IO
    {
        return io(fn() => unlink($path->toString()));
    }

    /**
     * Read file contents using bracket for resource safety
     *
     * @param Path $path
     * @return IO<string>
     */
    function readFileContents(Path $path): IO
    {
        return bracket(
            io(fn() => fopen($path->toString(), 'r')),
            fn($handle) => io(fn() => stream_get_contents($handle)),
            fn($handle) => io(fn() => fclose($handle))
        );
    }

    /**
     * Write string to file using bracket for resource safety
     *
     * @param Path $path
     * @param string $contents
     * @return IO<int> Number of bytes written
     */
    function writeFileContents(Path $path, string $contents): IO
    {
        return bracket(
            io(fn() => fopen($path->toString(), 'w')),
            fn($handle) => io(fn() => fwrite($handle, $contents)),
            fn($handle) => io(fn() => fclose($handle))
        );
    }

    /**
     * Read file line by line using bracket for resource safety
     *
     * @param Path $path
     * @return IO<array<string>>
     */
    function readLines(Path $path): IO
    {
        return bracket(
            io(fn() => fopen($path->toString(), 'r')),
            fn($handle) => io(function() use ($handle) {
                $lines = [];
                while (($line = fgets($handle)) !== false) {
                    $lines[] = rtrim($line, "\r\n");
                }
                return $lines;
            }),
            fn($handle) => io(fn() => fclose($handle))
        );
    }

    /**
     * Write lines to file using bracket for resource safety
     *
     * @param Path $path
     * @param array<string> $lines
     * @return IO<int> Number of lines written
     */
    function writeLines(Path $path, array $lines): IO
    {
        return bracket(
            io(fn() => fopen($path->toString(), 'w')),
            fn($handle) => io(function() use ($handle, $lines) {
                $count = 0;
                foreach ($lines as $line) {
                    fwrite($handle, $line . PHP_EOL);
                    $count++;
                }
                return $count;
            }),
            fn($handle) => io(fn() => fclose($handle))
        );
    }

    /**
     * Create a pipe function for writing stream elements to a file
     *
     * This is a stream pipe that writes each element of the stream to a file.
     * Each element is written on a new line. The pipe evaluates the stream
     * and performs the write operation, returning a stream containing the
     * number of lines written.
     *
     * @param Path $path The path to write to
     * @return callable A pipe function that takes a Stream and returns a Stream
     *
     * @example
     * $count = Stream(...['line1', 'line2', 'line3'])
     *     ->through(writeFile(new Path('/tmp/output.txt')))
     *     ->compile->drain
     *     ->unsafeRunSync();
     */
    function writeFile(Path $path): callable
    {
        return function(Stream $stream) use ($path): Stream {
            $elements = $stream->toArray();
            $writeIO = writeLines($path, array_map('strval', $elements));
            // Return a stream that when compiled, performs the write
            // For now, we'll eagerly write and return an empty stream
            // This is a simplified implementation for pure streams
            $writeIO->unsafeRunSync();
            return Stream(); // Return empty stream after write
        };
    }
}
