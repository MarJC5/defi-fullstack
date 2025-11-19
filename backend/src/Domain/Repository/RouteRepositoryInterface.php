<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Route;

interface RouteRepositoryInterface
{
    public function save(Route $route): void;

    public function findById(string $id): ?Route;

    /**
     * @return array<Route>
     */
    public function findByAnalyticCode(
        string $code,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array;

    /**
     * @return array<array{analyticCode: string, totalDistanceKm: float, group?: string, periodStart?: string, periodEnd?: string}>
     */
    public function getDistancesByAnalyticCode(
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
        string $groupBy = 'none'
    ): array;
}
