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
