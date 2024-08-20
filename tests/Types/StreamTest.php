<?php

namespace Tests\Phunkie\Streams\Types;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Phunkie\Streams\Type\Stream;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertSame;
use const Phunkie\Functions\numbers\increment;

class StreamTest extends TestCase
{
    #[Test]
    function it_creates_pure_streams()
    {
        assertSame('Stream<Pure, Int>', Stream(1, 2, 3)->showType());
        assertSame('Stream(1, 2, 3)', Stream(1, 2, 3)->toString());
    }

    #[Test]
    function it_can_be_compiled_into_another_structure()
    {
        assertEquals(ImmList(1, 2, 3), Stream(1, 2, 3)->compile->toList());
        assertEquals([1, 2, 3], Stream(1, 2, 3)->compile->toArray());
    }

    #[Test]
    function it_is_a_functor()
    {
        assertEquals(ImmList(2, 3, 4, 5), Stream(1, 2, 3, 4)->map(increment)->compile->toList());

        /** @var Stream $as */
        $as = Stream(1, 2, 3, 4)->as(1);
        assertEquals(ImmList(1, 1, 1, 1), $as->compile->toList());

        /** @var Stream $void */
        $void = Stream(1, 2, 3, 4)->void();
        assertEquals(ImmList(Unit(), Unit(), Unit(), Unit()), $void->compile->toList());

        /** @var Stream $zipWith */
        $zipWith = Stream(1, 2, 3, 4)->zipWith(increment);
        assertEquals(
            ImmList(Pair(1, 2), Pair(2, 3), Pair(3, 4), Pair(4, 5)),
            $zipWith->compile->toList()
        );
    }

    #[Test]
    function it_has_list_operations()
    {
        $a = Stream("John", "Yoko");
        $b = Stream("Paul", "Linda");

        assertEquals(ImmList("John", "Yoko", "Paul", "Linda"), $a->concat($b)->compile->toList());
    }
}