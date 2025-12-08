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

use Phunkie\Streams\Compilable;
use Phunkie\Streams\Showable;

/**
 * Pull represents a pull-based stream that can be compiled to produce values.
 *
 * @method static map(callable $f)
 * @method static flatMap(callable $f)
 * @method static flatten()
 * @method static evalMap(callable $f)
 * @method static evalTap(callable $f)
 * @method static evalFilter(callable $f)
 * @method static evalFlatMap(callable $f)
 * @method static interleave(Pull ...$pulls)
 * @method static take(int $n)
 * @method static filter(callable $f)
 * @method static takeWhile(callable $predicate)
 * @method static dropWhile(callable $predicate)
 * @method static chunk(int $size)
 * @method array getValues()
 * @method void setScope(Scope $scope)
 */
interface Pull extends Showable, Compilable, \Iterator
{
    public function pull();

    public function toStream(): Stream;
}
