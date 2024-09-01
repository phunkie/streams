<?php

namespace Phunkie\Streams\IO;

use Phunkie\Cats\IO as PhunkieIO;

/**
 * @property $run
 * @property $unsafeRunSync
 */
class IO extends PhunkieIO
{
    private $f;

    public function __construct($f)
    {
        $this->f = $f;
    }

    public function __get($property)
    {
        return match($property) {
            'run' => $this->run(),
            'unsafeRunSync' => $this->unsafeRunSync(),
            default => throw new \Error("value $property is not a member of IO")
        };
    }

    public function run()
    {
        return ($this->f)();
    }

    public function unsafeRunSync()
    {
        return $this->run()->map(fn ($x) => $x->run());
    }
}