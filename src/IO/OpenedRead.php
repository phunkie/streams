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

use Phunkie\Effect\IO\IO;
use Phunkie\Streams\Closeable;

/**
 * Reads chunk by chunk from a handle that an IO opens: the IO runs on the first pull, and the
 * handle is closed when it reaches EOF or when the stream is closed early.
 */
final class OpenedRead implements Resource, Closeable
{
    /** @var mixed the open stream handle, null before the first pull */
    private $handle;

    private bool $closed = false;

    /**
     * @param IO $open IO<resource>, run once, on the first pull
     */
    public function __construct(private IO $open)
    {
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param int $bytes Number of bytes to read
     * @return string Data chunk, or Resource::EOF when the handle is exhausted or closed
     */
    public function pull(int $bytes): string
    {
        if ($this->closed) {
            return Resource::EOF;
        }

        $this->handle ??= $this->opened();
        $data = fread($this->handle, $bytes);
        if (false === $data || ('' === $data && feof($this->handle))) {
            $this->close();

            return Resource::EOF;
        }

        return $data;
    }

    public function close(): void
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
        $this->closed = true;
    }

    /**
     * @return mixed the stream handle the IO opened
     */
    private function opened()
    {
        $handle = $this->open->unsafeRun();
        if (!is_resource($handle) || 'stream' !== get_resource_type($handle)) {
            throw new \InvalidArgumentException(sprintf('readHandle needs an IO that opens a stream resource, got %s.', get_debug_type($handle)));
        }

        return $handle;
    }
}
