<?php

namespace Phunkie\Streams\Ops\Stream;

use Phunkie\Streams\Type\Stream;
use Phunkie\Types\Kind;

/**
 * @method getPull() Phunkie\Streams\Type\Pull
 * @method as($b) Phunkie\Streams\Type\Stream
 */
trait FunctorOps
{
    use \Phunkie\Ops\FunctorOps;
    public function map($f): Kind | Stream
    {
        $this->getPull()->map($f);

        return $this;
    }

    public function imap(callable $f, callable $g): Kind | Stream
    {
        return $this->map($f);
    }
}
