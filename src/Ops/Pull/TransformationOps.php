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
 * Transformation pipeline operations for Pull. Manages the lazy transformation chain
 * that is applied when the pull is compiled.
 *
 * @method Scope getScope()
 */
trait TransformationOps
{
    /**
     * Append a transformation to the scope's pipeline, to be applied at compile time.
     *
     * @param Transformation $transformation The transformation to append
     * @return static
     */
    public function appendTransformation(Transformation $transformation): static
    {
        $this->getScope()->appendTransformation($transformation);

        return $this;
    }

    /**
     * Execute all pending transformations against the given chunk of data.
     * Returns IO if effectful transformations are present and $acceptIo is true.
     *
     * @param iterable $chunk The input data to transform
     * @param bool $acceptIo Whether to allow IO return (false forces array)
     * @return iterable|IO
     */
    public function runTransformations(iterable $chunk, $acceptIo = true): iterable | IO
    {
        return $this->getScope()->runTransformations($chunk, $acceptIo);
    }
}
