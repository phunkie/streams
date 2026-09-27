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
 * Holds the transformation pipeline of a stream.
 *
 * Operations append Transformations, composed into one pipeline. The compile path feeds it
 * one element at a time through emit(), stops pulling when it halts, and flushes what it
 * still buffers through finish().
 */
class Scope
{
    /** @var Transformation Composed transformation pipeline. */
    private Transformation $transformation;

    /**
     * Append a transformation to the pipeline.
     *
     * If no transformation exists yet, the given one becomes the root;
     * otherwise it is composed after the existing pipeline via andThen().
     *
     * @param Transformation $transformation The transformation to append.
     * @return void
     */
    public function appendTransformation(Transformation $transformation): void
    {
        if (!isset($this->transformation)) {
            $this->transformation = $transformation;

            return;
        }
        $this->transformation = $this->transformation->andThen($transformation);
    }

    /**
     * Run one chunk through the pipeline, effects included, and return the elements that come out.
     *
     * @param iterable $chunk
     * @return array
     */
    public function emit(iterable $chunk): array
    {
        if (!isset($this->transformation)) {
            return is_array($chunk) ? $chunk : iterator_to_array($chunk, false);
        }

        return $this->transformation->emit($chunk);
    }

    /**
     * The elements the pipeline still holds once the input is exhausted.
     *
     * @return array
     */
    public function finish(): array
    {
        return isset($this->transformation) ? $this->transformation->finish() : [];
    }

    /**
     * Whether a transformation has asked the source to stop pulling.
     *
     * @return bool
     */
    public function isHalted(): bool
    {
        return isset($this->transformation) && $this->transformation->isHalted();
    }

    /**
     * Whether compiling has to hand back an IO: the pipeline carries an effect that does more
     * than pass elements through, so its results only exist once that effect runs.
     *
     * @return bool
     */
    public function compilesToIo(): bool
    {
        return isset($this->transformation)
            && null !== $this->transformation->getEffect()
            && !$this->transformation->isPassthrough();
    }

    /**
     * Run the composed transformation pipeline against a whole chunk of data.
     *
     * For passthrough transformations (side-effect-only), the IO action is
     * executed immediately. When $acceptIo is false, the IO result is
     * unwrapped synchronously instead of being returned as an IO value.
     *
     * @param iterable $chunk    The data chunk to transform.
     * @param bool     $acceptIo When true, passthrough results may be returned as IO.
     * @return iterable|IO
     */
    public function runTransformations(iterable $chunk, $acceptIo = true): iterable | IO
    {
        if (!isset($this->transformation)) {
            return $chunk;
        }

        $result = $this->transformation->run($chunk);
        if (!$result instanceof IO || !$this->transformation->isPassthrough()) {
            return $result;
        }

        $value = $result->unsafeRun();

        return $acceptIo ? new IO(fn () => $value) : $value;
    }
}
