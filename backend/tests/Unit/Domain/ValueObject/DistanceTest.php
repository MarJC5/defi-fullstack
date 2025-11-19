<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidDistanceException;
use App\Domain\ValueObject\Distance;
use PHPUnit\Framework\TestCase;

class DistanceTest extends TestCase
{
    public function testFromKilometersCreatesValidDistance(): void
    {
        $distance = Distance::fromKilometers(5.5);

        $this->assertEquals(5.5, $distance->kilometers());
    }

    public function testFromKilometersThrowsExceptionForNegativeValue(): void
    {
        $this->expectException(InvalidDistanceException::class);
        $this->expectExceptionMessage('Distance cannot be negative');

        Distance::fromKilometers(-1.0);
    }

    public function testZeroCreatesZeroDistance(): void
    {
        $distance = Distance::zero();

        $this->assertEquals(0.0, $distance->kilometers());
        $this->assertTrue($distance->isZero());
    }

    public function testIsZeroReturnsFalseForNonZeroDistance(): void
    {
        $distance = Distance::fromKilometers(1.0);

        $this->assertFalse($distance->isZero());
    }

    public function testAddCombinesTwoDistances(): void
    {
        $distance1 = Distance::fromKilometers(5.0);
        $distance2 = Distance::fromKilometers(3.5);

        $result = $distance1->add($distance2);

        $this->assertEquals(8.5, $result->kilometers());
    }

    public function testIsGreaterThanComparesDistances(): void
    {
        $distance1 = Distance::fromKilometers(10.0);
        $distance2 = Distance::fromKilometers(5.0);

        $this->assertTrue($distance1->isGreaterThan($distance2));
        $this->assertFalse($distance2->isGreaterThan($distance1));
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $distance1 = Distance::fromKilometers(5.5);
        $distance2 = Distance::fromKilometers(5.5);

        $this->assertTrue($distance1->equals($distance2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $distance1 = Distance::fromKilometers(5.5);
        $distance2 = Distance::fromKilometers(5.6);

        $this->assertFalse($distance1->equals($distance2));
    }

    public function testToStringFormatsDistance(): void
    {
        $distance = Distance::fromKilometers(5.5);

        $this->assertEquals('5.50 km', (string) $distance);
    }
}
