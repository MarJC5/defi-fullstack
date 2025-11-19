<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Route;
use App\Domain\ValueObject\Distance;
use App\Domain\ValueObject\StationId;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testRouteCreationWithValueObjects(): void
    {
        $fromStation = StationId::fromString('MX');
        $toStation = StationId::fromString('CGE');
        $distance = Distance::fromKilometers(5.5);

        $route = new Route(
            'test-id-123',
            $fromStation,
            $toStation,
            'ANA-001',
            $distance,
            [$fromStation, $toStation]
        );

        $this->assertEquals('test-id-123', $route->getId());
        $this->assertTrue($route->getFromStationId()->equals($fromStation));
        $this->assertTrue($route->getToStationId()->equals($toStation));
        $this->assertEquals('ANA-001', $route->getAnalyticCode());
        $this->assertTrue($route->getDistance()->equals($distance));
        $this->assertEquals(5.5, $route->getDistanceKm());
        $this->assertCount(2, $route->getPath());
        $this->assertInstanceOf(\DateTimeImmutable::class, $route->getCreatedAt());
    }

    public function testRouteToArray(): void
    {
        $fromStation = StationId::fromString('A');
        $toStation = StationId::fromString('B');
        $midStation = StationId::fromString('C');
        $distance = Distance::fromKilometers(10.0);

        $route = new Route(
            'array-test-id',
            $fromStation,
            $toStation,
            'TEST-123',
            $distance,
            [$fromStation, $midStation, $toStation]
        );

        $array = $route->toArray();

        $this->assertEquals('array-test-id', $array['id']);
        $this->assertEquals('A', $array['fromStationId']);
        $this->assertEquals('B', $array['toStationId']);
        $this->assertEquals('TEST-123', $array['analyticCode']);
        $this->assertEquals(10.0, $array['distanceKm']);
        $this->assertEquals(['A', 'C', 'B'], $array['path']);
        $this->assertNotEmpty($array['createdAt']);
    }

    public function testGetCreatedAtReturnsDateTimeImmutable(): void
    {
        $fromStation = StationId::fromString('MX');
        $toStation = StationId::fromString('CGE');
        $distance = Distance::fromKilometers(2.5);

        $route = new Route(
            'time-test-id',
            $fromStation,
            $toStation,
            'TIME-001',
            $distance,
            [$fromStation, $toStation]
        );

        $createdAt = $route->getCreatedAt();

        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
        // Verify it was created recently (within last 5 seconds)
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $createdAt->getTimestamp();
        $this->assertLessThan(5, $diff);
    }

    public function testGetDistanceReturnsValueObject(): void
    {
        $fromStation = StationId::fromString('MX');
        $toStation = StationId::fromString('CGE');
        $distance = Distance::fromKilometers(7.5);

        $route = new Route(
            'dist-test-id',
            $fromStation,
            $toStation,
            'DIST-001',
            $distance,
            [$fromStation, $toStation]
        );

        $this->assertInstanceOf(Distance::class, $route->getDistance());
        $this->assertEquals(7.5, $route->getDistance()->kilometers());
    }
}
