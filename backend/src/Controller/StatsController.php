<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Handler\GetAnalyticDistancesHandler;
use App\Application\Query\GetAnalyticDistancesQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class StatsController extends AbstractController
{
    public function __construct(
        private readonly GetAnalyticDistancesHandler $handler
    ) {}

    #[Route('/stats/distances', name: 'get_analytic_distances', methods: ['GET'])]
    public function getAnalyticDistances(Request $request): JsonResponse
    {
        $from = $request->query->getString('from') ?: null;
        $to = $request->query->getString('to') ?: null;
        $groupBy = $request->query->getString('groupBy') ?: 'none';

        // Validate groupBy
        $validGroupBy = ['day', 'month', 'year', 'none'];
        if (!in_array($groupBy, $validGroupBy, true)) {
            return $this->json([
                'code' => 'INVALID_GROUP_BY',
                'message' => 'groupBy must be one of: ' . implode(', ', $validGroupBy),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate date range
        if ($from && $to && $from > $to) {
            return $this->json([
                'code' => 'INVALID_DATE_RANGE',
                'message' => 'from date must be before or equal to to date',
            ], Response::HTTP_BAD_REQUEST);
        }

        $query = new GetAnalyticDistancesQuery($from, $to, $groupBy);
        $result = $this->handler->handle($query);

        return $this->json($result);
    }
}
