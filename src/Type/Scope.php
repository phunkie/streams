<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Type;

use Phunkie\Effect\IO\IO;

/**
 * Manages the transformation pipeline and filtering state for a stream.
 *
 * A Scope accumulates map, filter, and composed Transformation operations
 * that are applied to each chunk when the stream is evaluated.
 */
class Scope
{
    /** @var callable[] */
    private array $callables = [];

    /** @var callable[] Accumulated map functions. */
    private array $maps = [];

    /** @var callable[] Accumulated filter predicates. */
    private array $filters = [];

    /** @var Transformation Composed transformation pipeline. */
    private Transformation $transformation;

    /**
     * Append a transformation to the pipeline.
     *
     * If no transformation exists yet, the given one becomes the root;
     * otherwise it is composed after the existing pipeline via andThen().
     */
    public function appendTransformation(Transformation $transformation): void
    {
        if (!isset($this->transformation)) {
            $this->transformation = $transformation;

            return;
        }
        $this->transformation = $this->transformation->andThen($transformation);
    }

    /** Register a map function to be applied to stream elements. */
    public function addMap(callable $f): void
    {
        $this->maps[] = $f;
    }

    /**
     * @return callable[]
     */
    public function getMaps(): array
    {
        return $this->maps;
    }

    /** Register a filter predicate to be applied to stream elements. */
    public function addFilter(callable $f): void
    {
        $this->filters[] = $f;
    }

    /**
     * @return callable[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Run the composed transformation pipeline against a chunk of data.
     *
     * For passthrough transformations (side-effect-only), the IO action is
     * executed immediately. When $acceptIo is false, the IO result is
     * unwrapped synchronously instead of being returned as an IO value.
     *
     * @param bool $acceptIo When true, passthrough results may be returned as IO.
     */
    public function runTransformations(iterable $chunk, $acceptIo = true): iterable | IO
    {
        if (!isset($this->transformation)) {
            return $chunk;
        }

        if ($this->transformation->isPassthrough()) {
            $io = $this->transformation->run($chunk);
            $io->unsafeRun();
            if ($acceptIo) {
                return $io;
            }

            return $io->unsafeRunSync();
        }

        return $this->transformation->run($chunk);
    }
}
