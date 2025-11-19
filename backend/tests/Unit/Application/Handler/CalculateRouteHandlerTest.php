<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Handler;

use App\Application\Command\CalculateRouteCommand;
use App\Application\Handler\CalculateRouteHandler;
use App\Domain\Entity\Route;
use App\Domain\Repository\RouteRepositoryInterface;
use App\Domain\Service\GraphBuilder;
use PHPUnit\Framework\TestCase;

class CalculateRouteHandlerTest extends TestCase
{
    private string $testDistancesPath;

    protected function setUp(): void
    {
        // Use the real distances file for handler tests
        $this->testDistancesPath = __DIR__ . '/../../../../data/distances.json';
    }

    public function testHandleCalculatesAndSavesRoute(): void
    {
        $repository = $this->createMock(RouteRepositoryInterface::class);
        $graphBuilder = new GraphBuilder();

        $repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Route $route) {
                return $route->getFromStationId()->value() === 'MX'
                    && $route->getToStationId()->value() === 'CGE'
                    && $route->getAnalyticCode() === 'TEST-001'
                    && $route->getDistanceKm() > 0
                    && count($route->getPath()) >= 2;
            }));

        $handler = new CalculateRouteHandler(
            $graphBuilder,
            $repository,
            $this->testDistancesPath
        );

        $command = new CalculateRouteCommand('MX', 'CGE', 'TEST-001');
        $route = $handler->handle($command);

        $this->assertEquals('MX', $route->getFromStationId());
        $this->assertEquals('CGE', $route->getToStationId());
        $this->assertEquals('TEST-001', $route->getAnalyticCode());
        $this->assertGreaterThan(0, $route->getDistanceKm());
        $this->assertEquals('MX', $route->getPath()[0]);
        $this->assertEquals('CGE', $route->getPath()[count($route->getPath()) - 1]);
    }

    public function testHandleReturnsSavedRoute(): void
    {
        $repository = $this->createMock(RouteRepositoryInterface::class);
        $graphBuilder = new GraphBuilder();

        $savedRoute = null;
        $repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Route $route) use (&$savedRoute) {
                $savedRoute = $route;
            });

        $handler = new CalculateRouteHandler(
            $graphBuilder,
            $repository,
            $this->testDistancesPath
        );

        $command = new CalculateRouteCommand('MX', 'VUAR', 'TEST-002');
        $route = $handler->handle($command);

        $this->assertSame($savedRoute, $route);
    }

    public function testHandleWithMultipleStops(): void
    {
        $repository = $this->createMock(RouteRepositoryInterface::class);
        $graphBuilder = new GraphBuilder();

        $handler = new CalculateRouteHandler(
            $graphBuilder,
            $repository,
            $this->testDistancesPath
        );

        $command = new CalculateRouteCommand('MX', 'BEMM', 'MULTI-001');
        $route = $handler->handle($command);

        // Route from MX to BEMM should have multiple stops
        $this->assertGreaterThan(2, count($route->getPath()));
        $this->assertEquals('MX', $route->getPath()[0]);
        $this->assertEquals('BEMM', $route->getPath()[count($route->getPath()) - 1]);
    }

    public function testHandleSameStationReturnsZeroDistance(): void
    {
        $repository = $this->createMock(RouteRepositoryInterface::class);
        $graphBuilder = new GraphBuilder();

        $handler = new CalculateRouteHandler(
            $graphBuilder,
            $repository,
            $this->testDistancesPath
        );

        $command = new CalculateRouteCommand('MX', 'MX', 'SAME-001');
        $route = $handler->handle($command);

        $this->assertEquals(0.0, $route->getDistanceKm());
        $this->assertEquals(['MX'], $route->getPath());
    }
}
