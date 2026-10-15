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
use Phunkie\Streams\Closeable;
use Phunkie\Streams\Type\Scope;
use Phunkie\Types\ImmList;

/**
 * Compilation operations shared by every pull: each element is pulled, run through the scope's
 * pipeline and handed on before the next one is pulled, so memory holds one element at a time
 * and a halting transformation stops the source early. A pull that is Closeable is closed once
 * the run is over, however it ended.
 *
 * @method \Generator elements()
 * @method Scope getScope()
 */
trait CompileOps
{
    /**
     * Compile into an ImmList, applying all pending transformations. Returns an IO when the
     * pipeline carries an effect whose results only exist once it runs.
     *
     * @return ImmList|IO
     */
    public function toList(): ImmList | IO
    {
        $list = $this->collect()->map(fn (array $elements) => ImmList(...$elements));

        return $this->getScope()->compilesToIo() ? $list : $list->unsafeRun();
    }

    /**
     * Compile into a plain PHP array, applying all pending transformations and running their effects.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collect()->unsafeRun();
    }

    /**
     * Run all transformations for their effects, discarding the output. Returns IO<Unit>.
     *
     * @return IO
     */
    public function drain(): IO
    {
        return new IO(function () {
            foreach ($this->outputs() as $ignored) {
            }

            return Unit();
        });
    }

    /**
     * Fold the output into one value, one element at a time, once the IO runs.
     *
     * @template B
     * @param B $initial
     * @param callable(B, mixed): B $f
     * @return IO<B>
     */
    public function fold(mixed $initial, callable $f): IO
    {
        return new IO(function () use ($initial, $f) {
            $accumulator = $initial;
            foreach ($this->outputs() as $element) {
                $accumulator = $f($accumulator, $element);
            }

            return $accumulator;
        });
    }

    /**
     * The output as a Generator for consumers outside the IO discipline: each element is pulled
     * and its effects run as the consumer iterates, and the source is closed when the generator
     * finishes or is abandoned.
     *
     * @return \Generator<int, mixed>
     */
    public function toGenerator(): \Generator
    {
        return $this->outputs();
    }

    /**
     * An IO that pulls every element through the pipeline and collects what comes out.
     *
     * @return IO<array>
     */
    private function collect(): IO
    {
        return new IO(fn () => iterator_to_array($this->outputs(), false));
    }

    /**
     * Pull one element at a time, run it through the pipeline and yield each result, until the
     * source ends or a transformation halts; then yield what the pipeline still holds. Whatever
     * way the run ends, a Closeable pull is closed once.
     *
     * @return \Generator<int, mixed>
     */
    private function outputs(): \Generator
    {
        $scope = $this->getScope();

        try {
            foreach ($this->elements() as $element) {
                yield from $scope->emit([$element]);
                if ($scope->isHalted()) {
                    break;
                }
            }

            yield from $scope->finish();
        } finally {
            if ($this instanceof Closeable) {
                $this->close();
            }
        }
    }
}
