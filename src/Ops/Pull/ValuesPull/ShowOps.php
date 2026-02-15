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

use function Phunkie\Functions\show\showArrayType;
use function Phunkie\Functions\show\showValue;

/**
 * Show operations for ValuesPull. Provides type and string representations.
 *
 * @method array getValues()
 */
trait ShowOps
{
    /**
     * Return the type representation based on the contained values.
     *
     * @return string
     */
    public function showType(): string
    {
        return showArrayType($this->getValues());
    }

    /**
     * Return a comma-separated string of the contained values.
     *
     * @return string
     */
    public function toString(): string
    {
        return join(', ', array_map(fn ($x) => showValue($x), $this->getValues()));
    }
}
