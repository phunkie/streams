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
        return call_user_func($this->f);
    }

    public function unsafeRunSync()
    {
        return $this->run();
    }
}