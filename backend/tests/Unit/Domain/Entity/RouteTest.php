<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testRouteCreation(): void
    {
        $route = new Route(
            'MX',
            'CGE',
            'ANA-001',
            5.5,
            ['MX', 'CGE']
        );

        $this->assertNotEmpty($route->getId());
        $this->assertEquals('MX', $route->getFromStationId());
        $this->assertEquals('CGE', $route->getToStationId());
        $this->assertEquals('ANA-001', $route->getAnalyticCode());
        $this->assertEquals(5.5, $route->getDistanceKm());
        $this->assertEquals(['MX', 'CGE'], $route->getPath());
        $this->assertInstanceOf(\DateTimeImmutable::class, $route->getCreatedAt());
    }

    public function testRouteToArray(): void
    {
        $route = new Route(
            'A',
            'B',
            'TEST-123',
            10.0,
            ['A', 'C', 'B']
        );

        $array = $route->toArray();

        $this->assertNotEmpty($array['id']);
        $this->assertEquals('A', $array['fromStationId']);
        $this->assertEquals('B', $array['toStationId']);
        $this->assertEquals('TEST-123', $array['analyticCode']);
        $this->assertEquals(10.0, $array['distanceKm']);
        $this->assertEquals(['A', 'C', 'B'], $array['path']);
        $this->assertNotEmpty($array['createdAt']);
    }

    public function testGetCreatedAtReturnsDateTimeImmutable(): void
    {
        $route = new Route(
            'MX',
            'CGE',
            'TIME-001',
            2.5,
            ['MX', 'CGE']
        );

        $createdAt = $route->getCreatedAt();

        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
        // Verify it was created recently (within last 5 seconds)
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $createdAt->getTimestamp();
        $this->assertLessThan(5, $diff);
    }
}
