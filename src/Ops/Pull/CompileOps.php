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
use Phunkie\Types\ImmList;

/**
 * Compilation operations shared by every pull: each element is pulled, run through the scope's
 * pipeline and handed on before the next one is pulled, so memory holds one element at a time
 * and a halting transformation stops the source early.
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
            $this->emitTo(fn () => null);

            return Unit();
        });
    }

    /**
     * An IO that pulls every element through the pipeline and collects what comes out.
     *
     * @return IO<array>
     */
    private function collect(): IO
    {
        return new IO(function () {
            $elements = [];
            $this->emitTo(function ($element) use (&$elements) {
                $elements[] = $element;
            });

            return $elements;
        });
    }

    /**
     * Pull one element at a time, run it through the pipeline and hand each result to the sink,
     * until the source ends or a transformation halts; then hand over what the pipeline still holds.
     *
     * @param callable $sink
     * @return void
     */
    private function emitTo(callable $sink): void
    {
        $scope = $this->getScope();
        foreach ($this->elements() as $element) {
            foreach ($scope->emit([$element]) as $out) {
                $sink($out);
            }
            if ($scope->isHalted()) {
                break;
            }
        }

        foreach ($scope->finish() as $out) {
            $sink($out);
        }
    }
}
