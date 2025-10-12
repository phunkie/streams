<?php

namespace Phunkie\Streams\Functions\resource {

    use Phunkie\Effect\IO\IO;
    use function Phunkie\Effect\Functions\io\bracket as effectBracket;

    /**
     * Re-export bracket from phunkie/effect for convenience.
     *
     * Creates an IO that will acquire a resource, use it, and then release it.
     * Ensures the release is always called, even if use fails.
     *
     * @template T
     * @template R
     * @param IO<T> $acquire The IO that acquires the resource
     * @param callable(T): IO<R> $use The function that uses the resource
     * @param callable(T): IO<void> $release The function that releases the resource
     * @return IO<R>
     *
     * @example
     * ```php
     * use function Phunkie\Streams\Functions\resource\bracket;
     * use function Phunkie\Effect\Functions\io\io;
     *
     * $result = bracket(
     *     io(fn() => fopen('file.txt', 'r')),
     *     fn($handle) => io(fn() => fread($handle, 1024)),
     *     fn($handle) => io(fn() => fclose($handle))
     * )->unsafeRunSync();
     * ```
     */
    function bracket(IO $acquire, callable $use, callable $release): IO
    {
        return effectBracket($acquire, $use, $release);
    }
}
