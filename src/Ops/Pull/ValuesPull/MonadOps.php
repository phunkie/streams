<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use function Phunkie\Streams\Functions\transformation\flatMap;
use function Phunkie\Streams\Functions\transformation\flatten;

/**
 * @method getScope
 */
trait MonadOps
{
    public function flatMap(callable $f): static
    {
        $this->appendTransformation(flatMap($f));

        return $this;
    }

    public function flatten(): static
    {
        $this->appendTransformation(flatten());

        return $this;
    }
}
