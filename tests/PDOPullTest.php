<?php

use PHPUnit\Framework\TestCase;
use Phunkie\Streams\Pull\PDOPull;

it('streams from PDO statement', function () {
    // Pest binds $this to a TestCase instance
    $stmt = $this->createMock(PDOStatement::class);

    $stmt->expects($this->exactly(3))
        ->method('fetch')
        ->with(PDO::FETCH_ASSOC)
        ->willReturnOnConsecutiveCalls(
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
            false
        );

    $pull = new PDOPull($stmt);
    $values = $pull->getValues();

    expect($values)->toHaveCount(2);
    expect($values[0])->toBe(['id' => 1, 'name' => 'Alice']);
    expect($values[1])->toBe(['id' => 2, 'name' => 'Bob']);
});

it('can be converted to Stream using helper', function () {
    $stmt = $this->createMock(PDOStatement::class);

    $stmt->expects($this->exactly(2))
        ->method('fetch')
        ->with(PDO::FETCH_ASSOC)
        ->willReturnOnConsecutiveCalls(
            ['name' => 'Apple'],
            false
        );

    $stream = StreamFromPDO($stmt);
    $list = $stream->compile()->toList();

    expect($list->toArray())->toBe([['name' => 'Apple']]);
});

it('transforms rows with map, filter and take before compiling', function () {
    $stmt = $this->createMock(PDOStatement::class);
    $stmt->method('fetch')->with(PDO::FETCH_ASSOC)->willReturnOnConsecutiveCalls(
        ['name' => 'Alice'],
        ['name' => 'Bob'],
        ['name' => 'Carol'],
        ['name' => 'Dave'],
        false
    );

    $names = StreamFromPDO($stmt)
        ->map(fn ($row) => $row['name'])
        ->filter(fn ($name) => $name !== 'Bob')
        ->take(2)
        ->compile()
        ->toList();

    expect($names->toArray())->toBe(['Alice', 'Carol']);
});

it('runs an effect for every row when drained', function () {
    $stmt = $this->createMock(PDOStatement::class);
    $stmt->method('fetch')->with(PDO::FETCH_ASSOC)->willReturnOnConsecutiveCalls(
        ['name' => 'Alice'],
        ['name' => 'Bob'],
        false
    );
    $seen = [];

    StreamFromPDO($stmt)
        ->evalTap(function ($row) use (&$seen) {
            return \Phunkie\Effect\Functions\io\io(function () use (&$seen, $row) {
                $seen[] = $row['name'];
            });
        })
        ->compile()
        ->drain()
        ->unsafeRun();

    expect($seen)->toBe(['Alice', 'Bob']);
});
