<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\IO;

use Phunkie\Streams\Type\Stream;

/**
 * Reads from a file resource chunk by chunk.
 *
 * Opens the file lazily on first pull and closes it on destruction.
 */
class Read implements Resource
{
    private $handle;

    /**
     * @param string $path Filesystem path to read from
     */
    public function __construct(private string $path)
    {
    }

    /** Close the file handle if still open. */
    public function __destruct()
    {
        if ($this->isOpen()) {
            $this->close();
        }
    }

    /**
     * Create a Stream that reads the entire file in chunks.
     *
     * @param string $path  Filesystem path to read from
     * @param int    $bytes Chunk size in bytes
     * @return Stream
     */
    public static function readAll($path, $bytes = 256): Stream
    {
        $stream = Stream(new Read($path));

        return $stream->setBytes($bytes);
    }

    /**
     * Pull the next chunk of bytes from the file.
     *
     * Opens the file on first call. Returns Resource::EOF at end-of-file.
     *
     * @param int $bytes Number of bytes to read
     * @return string Data chunk, or Resource::EOF when the file is exhausted
     */
    public function pull($bytes): string
    {
        if (!$this->isOpen()) {
            $this->open();
        }

        return $this->read($bytes);
    }

    /**
     * Check whether the file handle is an open stream resource.
     *
     * @return bool
     */
    private function isOpen(): bool
    {
        return is_resource($this->handle) && get_resource_type($this->handle) === 'stream';
    }

    /**
     * Open the file for reading.
     *
     * @return void
     */
    private function open(): void
    {
        $this->handle = fopen($this->path, 'r');
    }

    /**
     * Read up to $bytes from the handle, returning Resource::EOF at end-of-file.
     *
     * @return string
     */
    private function read($bytes): string
    {
        if (is_resource($this->handle)) {
            $data = fread($this->handle, $bytes);

            if ($data === false || ($data === '' && feof($this->handle))) {
                return Resource::EOF;
            }

            return $data;
        }

        throw new \Error("Not a valid resource");
    }

    /** Close the file handle. */
    private function close(): void
    {
        fclose($this->handle);
    }
}
