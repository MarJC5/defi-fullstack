<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Query\GetAnalyticDistancesQuery;
use App\Domain\Repository\RouteRepositoryInterface;

class GetAnalyticDistancesHandler
{
    public function __construct(
        private readonly RouteRepositoryInterface $repository
    ) {
    }

    public function handle(GetAnalyticDistancesQuery $query): array
    {
        $from = $query->from ? new \DateTimeImmutable($query->from) : null;
        $to = $query->to ? new \DateTimeImmutable($query->to) : null;

        $items = $this->repository->getDistancesByAnalyticCode($from, $to, $query->groupBy);

        return [
            'from' => $query->from,
            'to' => $query->to,
            'groupBy' => $query->groupBy,
            'items' => $items,
        ];
    }
}
