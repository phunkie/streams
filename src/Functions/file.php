<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace {

    use Phunkie\Streams\IO\File\Path as PathClass;

    /**
     * Creates a Path from a string
     *
     * @param string $pathname The path string
     * @return PathClass The Path object
     */
    function Path(string $pathname): PathClass
    {
        return new PathClass($pathname);
    }
}

namespace Phunkie\Streams\Functions\file {

    use function Phunkie\Effect\Functions\io\io;

    use Phunkie\Effect\IO\IO;

    use function Phunkie\Streams\Functions\resource\bracket;

    use Phunkie\Streams\IO\File\Path;
    use Phunkie\Streams\IO\Read;
    use Phunkie\Streams\Type\Stream;

    /**
     * Read all contents from a file as a Stream
     *
     * @param Path $path The file path to read from
     * @param int $chunk The chunk size in bytes for reading
     * @return Stream<string> A stream of file contents
     */
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
        return io(fn () => file_exists($path->toString()));
    }

    /**
     * Delete a file
     *
     * @param Path $path
     * @return IO<bool>
     */
    function deleteFile(Path $path): IO
    {
        return io(fn () => unlink($path->toString()));
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
            io(function () use ($path) {
                set_error_handler(function (int $errno, string $errstr): bool {
                    return true; // Suppress the error
                });
                $handle = fopen($path->toString(), 'r');
                restore_error_handler();

                if ($handle === false) {
                    throw new \RuntimeException("Failed to open file: " . $path->toString());
                }

                return $handle;
            }),
            fn ($handle) => io(fn () => stream_get_contents($handle)),
            fn ($handle) => io(fn () => fclose($handle))
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
            io(function () use ($path) {
                set_error_handler(function (int $errno, string $errstr): bool {
                    return true; // Suppress the error
                });
                $handle = fopen($path->toString(), 'w');
                restore_error_handler();

                if ($handle === false) {
                    throw new \RuntimeException("Failed to open file: " . $path->toString());
                }

                return $handle;
            }),
            fn ($handle) => io(fn () => fwrite($handle, $contents)),
            fn ($handle) => io(fn () => fclose($handle))
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
            io(function () use ($path) {
                set_error_handler(function (int $errno, string $errstr): bool {
                    return true; // Suppress the error
                });
                $handle = fopen($path->toString(), 'r');
                restore_error_handler();

                if ($handle === false) {
                    throw new \RuntimeException("Failed to open file: " . $path->toString());
                }

                return $handle;
            }),
            fn ($handle) => io(function () use ($handle) {
                $lines = [];
                while (($line = fgets($handle)) !== false) {
                    $lines[] = rtrim($line, "\r\n");
                }

                return $lines;
            }),
            fn ($handle) => io(fn () => fclose($handle))
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
            io(function () use ($path) {
                set_error_handler(function (int $errno, string $errstr): bool {
                    return true; // Suppress the error
                });
                $handle = fopen($path->toString(), 'w');
                restore_error_handler();

                if ($handle === false) {
                    throw new \RuntimeException("Failed to open file: " . $path->toString());
                }

                return $handle;
            }),
            fn ($handle) => io(function () use ($handle, $lines) {
                $count = 0;
                foreach ($lines as $line) {
                    fwrite($handle, $line . PHP_EOL);
                    $count++;
                }

                return $count;
            }),
            fn ($handle) => io(fn () => fclose($handle))
        );
    }

    /**
     * Create a pipe function for writing stream elements to a file
     *
     * This is a stream pipe that writes each element of the stream to a file.
     * Each element is written on a new line. The pipe uses true streaming,
     * processing elements one at a time without materializing the entire stream
     * into memory. This allows writing arbitrarily large streams with constant
     * memory usage.
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
        return function (Stream $stream) use ($path): Stream {
            // Open the file and write elements one at a time with true streaming
            $writeIO = bracket(
                io(function () use ($path) {
                    set_error_handler(function (int $errno, string $errstr): bool {
                        return true; // Suppress the error
                    });
                    $handle = fopen($path->toString(), 'w');
                    restore_error_handler();

                    if ($handle === false) {
                        throw new \RuntimeException("Failed to open file: " . $path->toString());
                    }

                    return $handle;
                }),
                fn ($handle) => io(function () use ($handle, $stream) {
                    $count = 0;
                    $stream
                        ->evalTap(function ($value) use ($handle, &$count) {
                            return io(function () use ($handle, $value, &$count) {
                                fwrite($handle, strval($value) . PHP_EOL);
                                $count++;
                            });
                        })
                        ->compile()
                        ->drain()
                        ->unsafeRun();

                    return $count;
                }),
                fn ($handle) => io(fn () => fclose($handle))
            );

            $writeIO->unsafeRunSync();

            return Stream(); // Return empty stream after write
        };
    }
}
