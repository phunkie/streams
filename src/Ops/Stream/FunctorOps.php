<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
