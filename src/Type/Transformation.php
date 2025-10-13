<?php

namespace Phunkie\Streams\Type;

use Phunkie\Effect\IO\IO;

class Transformation implements \ArrayAccess
{
    private \Closure $f;
    private mixed $effect = null;

    private bool $isPassthrough = false;

    public function __construct(\Closure $f)
    {
        $this->f = $f;
    }

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

    public function andThen(Transformation $transformation): Transformation
    {
        return new Transformation(fn ($chunk) => $transformation->run($this->run($chunk)));
    }

    public function offsetExists(mixed $offset): bool
    {
        throw new \Error('Transformation is not an array');
    }

    public function offsetGet(mixed $offset): mixed
    {
        $this->effect = $offset;

        return $this;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \Error('Transformation is not an array');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \Error('Transformation is not an array');
    }

    private function isEffectful(): bool
    {
        return !is_null($this->effect);
    }

    public function isPassthrough(): bool
    {
        return $this->isPassthrough;
    }

    public function setPassthrough(bool $isPassthrough): void
    {
        $this->isPassthrough = $isPassthrough;
    }

    public function getEffect(): mixed
    {
        return $this->effect;
    }

    public function setEffect(mixed $effect): Transformation
    {
        $this->effect = $effect;

        return $this;
    }
}
