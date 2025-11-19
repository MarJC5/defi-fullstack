<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Exception\NoRouteFoundException;
use App\Domain\Exception\StationNotFoundException;
use App\Domain\Service\RouteCalculator;
use PHPUnit\Framework\TestCase;

class RouteCalculatorTest extends TestCase
{
    private RouteCalculator $calculator;

    protected function setUp(): void
    {
        // Simple graph for testing:
        // A --1.0-- B --2.0-- C
        //           |
        //          1.5
        //           |
        //           D
        $graph = [
            'A' => ['B' => 1.0],
            'B' => ['A' => 1.0, 'C' => 2.0, 'D' => 1.5],
            'C' => ['B' => 2.0],
            'D' => ['B' => 1.5],
        ];

        $this->calculator = new RouteCalculator($graph);
    }

    public function testCalculateRouteForAdjacentStations(): void
    {
        $result = $this->calculator->calculate('A', 'B', 'TEST-001');

        $this->assertEquals(['A', 'B'], $result->getPath());
        $this->assertEquals(1.0, $result->getDistanceKm());
        $this->assertEquals('A', $result->getFromStationId());
        $this->assertEquals('B', $result->getToStationId());
        $this->assertEquals('TEST-001', $result->getAnalyticCode());
    }

    public function testCalculateRouteWithMultipleStops(): void
    {
        $result = $this->calculator->calculate('A', 'C', 'TEST-002');

        $this->assertEquals(['A', 'B', 'C'], $result->getPath());
        $this->assertEquals(3.0, $result->getDistanceKm());
    }

    public function testCalculateRouteForSameStation(): void
    {
        $result = $this->calculator->calculate('A', 'A', 'TEST-003');

        $this->assertEquals(['A'], $result->getPath());
        $this->assertEquals(0.0, $result->getDistanceKm());
    }

    public function testCalculateShortestPath(): void
    {
        $result = $this->calculator->calculate('A', 'D', 'TEST-004');

        $this->assertEquals(['A', 'B', 'D'], $result->getPath());
        $this->assertEquals(2.5, $result->getDistanceKm());
    }

    public function testThrowsExceptionForUnknownFromStation(): void
    {
        $this->expectException(StationNotFoundException::class);

        $this->calculator->calculate('X', 'B', 'TEST-005');
    }

    public function testThrowsExceptionForUnknownToStation(): void
    {
        $this->expectException(StationNotFoundException::class);

        $this->calculator->calculate('A', 'X', 'TEST-006');
    }

    public function testThrowsExceptionForDisconnectedStations(): void
    {
        // Graph with disconnected node
        $graph = [
            'A' => ['B' => 1.0],
            'B' => ['A' => 1.0],
            'C' => [], // Disconnected
        ];

        $calculator = new RouteCalculator($graph);

        $this->expectException(NoRouteFoundException::class);

        $calculator->calculate('A', 'C', 'TEST-007');
    }
}
