<?php

namespace Phunkie\Streams\IO;

use Phunkie\Streams\Type\Stream;

class Read implements Resource
{
    private $handle;

    public function __construct(private string $path)
    {
    }

    public function __destruct()
    {
        if ($this->isOpen()) {
            $this->close();
        }
    }

    public static function readAll($path, $bytes = 256): Stream
    {
        $stream = Stream(new Read($path));
        return $stream->setBytes($bytes);
    }

    public function pull($bytes)
    {
        if (!$this->isOpen()) {
            $this->open();
        }

        return $this->read($bytes);
    }

    private function isOpen()
    {
        return is_resource($this->handle) && get_resource_type($this->handle) === 'stream';
    }

    private function open()
    {
        $this->handle = fopen($this->path, 'r');
    }

    private function read($bytes)
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

    private function close(): void
    {
        fclose($this->handle);
    }
}