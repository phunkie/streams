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

class Task
{
    private \Closure $computation;

    /**
     * Task constructor.
     *
     * @param \Closure $computation A function that performs the task's computation.
     */
    public function __construct(\Closure $computation)
    {
        $this->computation = $computation;
    }

    /**
     * Map over the result of the Task.
     *
     * @param \Closure $f A function to transform the result of the task.
     * @return Task A new Task with the transformed result.
     */
    public function map(\Closure $f): Task
    {
        return new Task(function() use ($f) {
            return $f(($this->computation)());
        });
    }

    /**
     * FlatMap the result of the Task.
     *
     * @param callable $f A function that returns a new Task based on the result.
     * @return Task The result of applying the function to the task's result.
     */
    public function flatMap(\Closure $f): Task
    {
        return new Task(function() use ($f) {
            $result = ($this->computation)();
            $newTask = $f($result);
            return $newTask->run();
        });
    }

    /**
     * Execute the Task and return its result.
     *
     * @return mixed The result of the computation.
     */
    public function run()
    {
        return ($this->computation)();
    }

    /**
     * Run the Task asynchronously by wrapping it in a Fiber.
     *
     * @return \Fiber
     */
    public function runAsync(): \Fiber
    {
        $fiber = new \Fiber($this->computation);
        $fiber->start();
        return $fiber;
    }
}