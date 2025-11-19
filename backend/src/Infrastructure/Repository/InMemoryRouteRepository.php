<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Route;
use App\Domain\Repository\RouteRepositoryInterface;
use App\Infrastructure\Persistence\PeriodCalculator;

class InMemoryRouteRepository implements RouteRepositoryInterface
{
    /**
     * @var array<string, Route>
     */
    private array $routes = [];

    public function save(Route $route): void
    {
        $this->routes[$route->getId()] = $route;
    }

    public function findById(string $id): ?Route
    {
        return $this->routes[$id] ?? null;
    }

    /**
     * @return array<Route>
     */
    public function findByAnalyticCode(
        string $code,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array {
        return array_filter(
            $this->routes,
            function (Route $route) use ($code, $from, $to) {
                if ($route->getAnalyticCode() !== $code) {
                    return false;
                }

                $createdAt = $route->getCreatedAt();

                if ($from !== null && $createdAt < $from) {
                    return false;
                }

                if ($to !== null && $createdAt > $to->setTime(23, 59, 59)) {
                    return false;
                }

                return true;
            }
        );
    }

    /**
     * @return array<array{analyticCode: string, totalDistanceKm: float, group?: string, periodStart?: string, periodEnd?: string}>
     */
    public function getDistancesByAnalyticCode(
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
        string $groupBy = 'none'
    ): array {
        $aggregations = [];

        foreach ($this->routes as $route) {
            $createdAt = $route->getCreatedAt();

            if ($from !== null && $createdAt < $from) {
                continue;
            }

            if ($to !== null && $createdAt > $to->setTime(23, 59, 59)) {
                continue;
            }

            $analyticCode = $route->getAnalyticCode();
            $group = match ($groupBy) {
                'day' => $createdAt->format('Y-m-d'),
                'month' => $createdAt->format('Y-m'),
                'year' => $createdAt->format('Y'),
                default => 'all',
            };

            $key = $analyticCode . '_' . $group;

            if (!isset($aggregations[$key])) {
                $aggregations[$key] = [
                    'analyticCode' => $analyticCode,
                    'totalDistanceKm' => 0.0,
                ];

                if ($groupBy !== 'none') {
                    $aggregations[$key]['group'] = $group;
                    [$periodStart, $periodEnd] = PeriodCalculator::calculatePeriodDates($group, $groupBy);
                    $aggregations[$key]['periodStart'] = $periodStart;
                    $aggregations[$key]['periodEnd'] = $periodEnd;
                }
            }

            $aggregations[$key]['totalDistanceKm'] += $route->getDistanceKm();
        }

        return array_values($aggregations);
    }
}
