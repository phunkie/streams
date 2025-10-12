<?php

namespace Phunkie\Streams\Ops\Pull;

use Phunkie\Effect\IO\IO;
use Phunkie\Streams\Type\Transformation;

/**
 * @method getScope(): Phunkie\Streams\Type\Scope
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