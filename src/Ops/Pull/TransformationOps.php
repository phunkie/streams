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

use Phunkie\Effect\IO\IO;
use Phunkie\Streams\Type\Scope;
use Phunkie\Streams\Type\Transformation;

/**
 * @method Scope getScope()
 */
trait TransformationOps
{
    public function appendTransformation(Transformation $transformation): static
    {
        $this->getScope()->appendTransformation($transformation);

        return $this;
    }

    public function runTransformations(iterable $chunk, $acceptIo = true): iterable | IO
    {
        return $this->getScope()->runTransformations($chunk, $acceptIo);
    }
}
