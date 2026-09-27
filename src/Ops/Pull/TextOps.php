<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull;

use function Phunkie\Streams\Functions\transformation\lines;

/**
 * Text operations for pulls whose elements are chunks of text.
 *
 * @method \Phunkie\Streams\Type\Scope getScope()
 */
trait TextOps
{
    /**
     * Register a lines transformation: the chunks become lines, however the chunks were cut.
     *
     * @return static
     */
    public function lines(): static
    {
        $this->appendTransformation(lines());

        return $this;
    }
}
