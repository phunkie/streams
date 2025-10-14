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
 */
trait MonadOps
{
    public function flatMap(callable $f): Kind | Stream
    {
        $this->getPull()->flatMap($f);

        return $this;
    }

    public function flatten(): Kind | Stream
    {
        $this->getPull()->flatten();

        return $this;
    }

    public function ap(Kind $f): Kind | Stream
    {
        return $f->flatMap(fn ($g) => $this->map($g));
    }

    public function bind(callable $f): Kind | Stream
    {
        return $this->flatMap($f);
    }
}
