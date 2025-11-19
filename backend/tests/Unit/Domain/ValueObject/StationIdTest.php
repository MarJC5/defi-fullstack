<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidStationIdException;
use App\Domain\ValueObject\StationId;
use PHPUnit\Framework\TestCase;

class StationIdTest extends TestCase
{
    public function testFromStringCreatesValidStationId(): void
    {
        $stationId = StationId::fromString('MX');

        $this->assertEquals('MX', $stationId->value());
        $this->assertEquals('MX', (string) $stationId);
    }

    public function testFromStringTrimsWhitespace(): void
    {
        $stationId = StationId::fromString('  CGE  ');

        $this->assertEquals('CGE', $stationId->value());
    }

    public function testFromStringThrowsExceptionForEmptyValue(): void
    {
        $this->expectException(InvalidStationIdException::class);
        $this->expectExceptionMessage('Station ID cannot be empty');

        StationId::fromString('');
    }

    public function testFromStringThrowsExceptionForWhitespaceOnly(): void
    {
        $this->expectException(InvalidStationIdException::class);
        $this->expectExceptionMessage('Station ID cannot be empty');

        StationId::fromString('   ');
    }

    public function testFromStringThrowsExceptionForTooLongValue(): void
    {
        $this->expectException(InvalidStationIdException::class);
        $this->expectExceptionMessage('Station ID cannot exceed 10 characters');

        StationId::fromString('VERYLONGSTATIONID');
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $stationId1 = StationId::fromString('MX');
        $stationId2 = StationId::fromString('MX');

        $this->assertTrue($stationId1->equals($stationId2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $stationId1 = StationId::fromString('MX');
        $stationId2 = StationId::fromString('CGE');

        $this->assertFalse($stationId1->equals($stationId2));
    }
}
