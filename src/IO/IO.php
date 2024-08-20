<?php

namespace Phunkie\Streams\IO;

use Phunkie\Cats\IO as PhunkieIO;

class IO extends PhunkieIO
{
    private $f;

    public function __construct($f)
    {
        $this->f = $f;
    }

    public function run()
    {
        return ($this->f)();
    }

    public function unsafeRunSync()
    {
        return $this->run();
    }
}