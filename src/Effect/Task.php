<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Effect;

use Closure;

/**
 * @template T
 */
class Task
{
    /**
     * @var Closure(): T
     */
    private Closure $computation;

    /**
     * Task constructor.
     *
     * @param Closure(): T $computation A function that performs the task's computation.
     */
    public function __construct(Closure $computation)
    {
        $this->computation = $computation;
    }

    /**
     * Map over the result of the Task.
     *
     * @template U
     * @param Closure(T): U $f A function to transform the result of the task.
     * @return Task<U> A new Task with the transformed result.
     */
    public function map(Closure $f): Task
    {
        return new Task(function () use ($f) {
            return $f(($this->computation)());
        });
    }

    /**
     * FlatMap the result of the Task.
     *
     * @template U
     * @param Closure(T): Task<U> $f A function that returns a new Task based on the result.
     * @return Task<U> The result of applying the function to the task's result.
     */
    public function flatMap(Closure $f): Task
    {
        return new Task(function () use ($f) {
            $result = ($this->computation)();
            $newTask = $f($result);

            return $newTask->run();
        });
    }

    /**
     * Execute the Task and return its result.
     *
     * @return T The result of the computation.
     */
    public function run(): mixed
    {
        return ($this->computation)();
    }
}
