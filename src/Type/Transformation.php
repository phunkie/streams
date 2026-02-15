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
 * Wraps a closure as a composable stream transformation.
 *
 * Transformations can be chained via andThen() and optionally bound to an
 * effect class (e.g. IO) using array-access syntax: $transformation[IO::class].
 * Passthrough mode marks a transformation as side-effect-only, meaning the
 * original data passes through unchanged after the effect executes.
 */
class Transformation implements \ArrayAccess
{
    /** @var \Closure The transformation function applied to each chunk. */
    private \Closure $f;

    /** @var class-string|null The effect class to wrap the result in, or null for pure transforms. */
    private mixed $effect = null;

    /** @var bool When true, the transformation is side-effect-only (data passes through). */
    private bool $isPassthrough = false;

    /** @param \Closure $f The transformation function to apply to each chunk. */
    public function __construct(\Closure $f)
    {
        $this->f = $f;
    }

    /**
     * Apply this transformation to a chunk of data.
     *
     * If an effect class is set, the result is wrapped in a new instance
     * of that effect. Otherwise the closure is invoked directly.
     *
     * @throws \Error If the configured effect class cannot be instantiated.
     */
    public function run(iterable $chunk): iterable | IO
    {
        if ($this->isEffectful()) {
            try {
                $effectClass = new \ReflectionClass($this->effect);

                return $effectClass->newInstance(fn () => ($this->f)($chunk));
            } catch (\ReflectionException $e) {
                throw new \Error($this->effect . " is not an Effect");
            }
        }

        return ($this->f)($chunk);
    }

    /** Compose this transformation with another, producing a new pipeline. */
    public function andThen(Transformation $transformation): Transformation
    {
        return new Transformation(fn ($chunk) => $transformation->run($this->run($chunk)));
    }

    /** @throws \Error Always -- Transformation does not support isset via ArrayAccess. */
    public function offsetExists(mixed $offset): bool
    {
        throw new \Error('Transformation is not an array');
    }

    /**
     * Set the effect class via array-access syntax (e.g. $t[IO::class]).
     *
     * @param class-string $offset Fully-qualified effect class name.
     * @return $this
     */
    public function offsetGet(mixed $offset): mixed
    {
        $this->effect = $offset;

        return $this;
    }

    /** @throws \Error Always -- Transformation does not support []= assignment. */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \Error('Transformation is not an array');
    }

    /** @throws \Error Always -- Transformation does not support unset via ArrayAccess. */
    public function offsetUnset(mixed $offset): void
    {
        throw new \Error('Transformation is not an array');
    }

    /** Whether this transformation wraps its result in an effect. */
    private function isEffectful(): bool
    {
        return !is_null($this->effect);
    }

    /** Whether this transformation is side-effect-only (data passes through). */
    public function isPassthrough(): bool
    {
        return $this->isPassthrough;
    }

    /** Mark this transformation as passthrough (side-effect-only) or not. */
    public function setPassthrough(bool $isPassthrough): void
    {
        $this->isPassthrough = $isPassthrough;
    }

    /**
     * @return class-string|null The effect class name, or null if pure.
     */
    public function getEffect(): mixed
    {
        return $this->effect;
    }

    /**
     * @param class-string|null $effect The effect class name to wrap results in.
     * @return $this
     */
    public function setEffect(mixed $effect): Transformation
    {
        $this->effect = $effect;

        return $this;
    }
}
