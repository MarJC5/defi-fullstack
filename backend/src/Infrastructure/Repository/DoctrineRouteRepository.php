<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Route;
use App\Domain\Repository\RouteRepositoryInterface;
use App\Infrastructure\Persistence\PeriodCalculator;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineRouteRepository implements RouteRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Route $route): void
    {
        $this->entityManager->persist($route);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Route
    {
        return $this->entityManager->find(Route::class, $id);
    }

    /**
     * @return array<Route>
     */
    public function findByAnalyticCode(
        string $code,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(Route::class, 'r')
            ->where('r.analyticCode = :code')
            ->setParameter('code', $code)
            ->orderBy('r.createdAt', 'DESC');

        if ($from !== null) {
            $qb->andWhere('r.createdAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to !== null) {
            $endOfDay = $to->setTime(23, 59, 59);
            $qb->andWhere('r.createdAt <= :to')
                ->setParameter('to', $endOfDay);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<array{analyticCode: string, totalDistanceKm: float, group?: string, periodStart?: string, periodEnd?: string}>
     */
    public function getDistancesByAnalyticCode(
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
        string $groupBy = 'none'
    ): array {
        $groupExpression = match ($groupBy) {
            'day' => "TO_CHAR(r.created_at, 'YYYY-MM-DD')",
            'month' => "TO_CHAR(r.created_at, 'YYYY-MM')",
            'year' => "TO_CHAR(r.created_at, 'YYYY')",
            default => "'all'",
        };

        $sql = sprintf(
            'SELECT r.analytic_code AS "analyticCode",
                    SUM(r.distance_km) AS "totalDistanceKm"
                    %s
             FROM routes r
             WHERE 1=1',
            $groupBy !== 'none' ? ", $groupExpression AS \"group\"" : ''
        );

        $params = [];

        if ($from !== null) {
            $sql .= ' AND r.created_at >= :from';
            $params['from'] = $from->format('Y-m-d H:i:s');
        }

        if ($to !== null) {
            $endOfDay = $to->setTime(23, 59, 59);
            $sql .= ' AND r.created_at <= :to';
            $params['to'] = $endOfDay->format('Y-m-d H:i:s');
        }

        $sql .= ' GROUP BY r.analytic_code';

        if ($groupBy !== 'none') {
            $sql .= ", $groupExpression";
        }

        if ($groupBy !== 'none') {
            $sql .= ' ORDER BY "group", r.analytic_code';
        } else {
            $sql .= ' ORDER BY r.analytic_code';
        }

        $connection = $this->entityManager->getConnection();
        $result = $connection->executeQuery($sql, $params)->fetchAllAssociative();

        return array_map(function (array $row) use ($groupBy): array {
            $data = [
                'analyticCode' => $row['analyticCode'],
                'totalDistanceKm' => (float) $row['totalDistanceKm'],
            ];

            if ($groupBy !== 'none' && isset($row['group'])) {
                $group = $row['group'];
                $data['group'] = $group;

                // Calculate periodStart and periodEnd based on groupBy
                [$periodStart, $periodEnd] = PeriodCalculator::calculatePeriodDates($group, $groupBy);
                $data['periodStart'] = $periodStart;
                $data['periodEnd'] = $periodEnd;
            }

            return $data;
        }, $result);
    }
}
