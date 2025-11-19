<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\Entity\Route;
use App\Domain\Repository\RouteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineRouteRepositoryTest extends KernelTestCase
{
    private RouteRepositoryInterface $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(RouteRepositoryInterface::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Clean up database before each test
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('TRUNCATE TABLE routes CASCADE');
    }

    public function testSaveAndFindById(): void
    {
        $route = new Route(
            'MX',
            'CGE',
            'TEST-001',
            2.5,
            ['MX', 'CGE']
        );

        $this->repository->save($route);

        $found = $this->repository->findById($route->getId());

        $this->assertNotNull($found);
        $this->assertEquals($route->getId(), $found->getId());
        $this->assertEquals('MX', $found->getFromStationId());
        $this->assertEquals('CGE', $found->getToStationId());
        $this->assertEquals('TEST-001', $found->getAnalyticCode());
        $this->assertEquals(2.5, $found->getDistanceKm());
        $this->assertEquals(['MX', 'CGE'], $found->getPath());
    }

    public function testFindByAnalyticCode(): void
    {
        $route1 = new Route('MX', 'CGE', 'CODE-001', 2.5, ['MX', 'CGE']);
        $route2 = new Route('CGE', 'VUAR', 'CODE-001', 1.5, ['CGE', 'VUAR']);
        $route3 = new Route('MX', 'VUAR', 'CODE-002', 4.0, ['MX', 'CGE', 'VUAR']);

        $this->repository->save($route1);
        $this->repository->save($route2);
        $this->repository->save($route3);

        $routes = $this->repository->findByAnalyticCode('CODE-001');

        $this->assertCount(2, $routes);
        foreach ($routes as $route) {
            $this->assertEquals('CODE-001', $route->getAnalyticCode());
        }
    }

    public function testGetDistancesByAnalyticCode(): void
    {
        $route1 = new Route('MX', 'CGE', 'STATS-001', 2.5, ['MX', 'CGE']);
        $route2 = new Route('CGE', 'VUAR', 'STATS-001', 1.5, ['CGE', 'VUAR']);
        $route3 = new Route('MX', 'VUAR', 'STATS-002', 4.0, ['MX', 'CGE', 'VUAR']);

        $this->repository->save($route1);
        $this->repository->save($route2);
        $this->repository->save($route3);

        $distances = $this->repository->getDistancesByAnalyticCode();

        $this->assertNotEmpty($distances);

        $stats001 = null;
        $stats002 = null;
        foreach ($distances as $item) {
            if ($item['analyticCode'] === 'STATS-001') {
                $stats001 = $item;
            }
            if ($item['analyticCode'] === 'STATS-002') {
                $stats002 = $item;
            }
        }

        $this->assertNotNull($stats001);
        $this->assertEquals(4.0, $stats001['totalDistanceKm']); // 2.5 + 1.5

        $this->assertNotNull($stats002);
        $this->assertEquals(4.0, $stats002['totalDistanceKm']);
    }

    public function testGetDistancesByAnalyticCodeWithDateFilter(): void
    {
        $route = new Route('MX', 'CGE', 'DATE-001', 2.5, ['MX', 'CGE']);
        $this->repository->save($route);

        $today = new \DateTimeImmutable();
        $yesterday = $today->modify('-1 day');
        $tomorrow = $today->modify('+1 day');

        // Should find route created today
        $distances = $this->repository->getDistancesByAnalyticCode($yesterday, $tomorrow);
        $this->assertNotEmpty($distances);

        // Should not find route with past date range
        $lastWeek = $today->modify('-1 week');
        $lastWeekEnd = $today->modify('-6 days');
        $distances = $this->repository->getDistancesByAnalyticCode($lastWeek, $lastWeekEnd);

        $found = false;
        foreach ($distances as $item) {
            if ($item['analyticCode'] === 'DATE-001') {
                $found = true;
            }
        }
        $this->assertFalse($found);
    }

    public function testGetDistancesByAnalyticCodeWithGroupBy(): void
    {
        $route = new Route('MX', 'CGE', 'GROUP-001', 2.5, ['MX', 'CGE']);
        $this->repository->save($route);

        $distances = $this->repository->getDistancesByAnalyticCode(null, null, 'month');

        $this->assertNotEmpty($distances);
        $this->assertArrayHasKey('group', $distances[0]);
        $this->assertEquals(date('Y-m'), $distances[0]['group']);
    }
}
