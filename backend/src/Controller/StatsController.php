<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Handler\GetAnalyticDistancesHandler;
use App\Application\Query\GetAnalyticDistancesQuery;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
#[OA\Tag(name: 'Analytics')]
class StatsController extends AbstractController
{
    public function __construct(
        private readonly GetAnalyticDistancesHandler $handler
    ) {
    }

    #[Route('/stats/distances', name: 'get_analytic_distances', methods: ['GET'])]
    #[OA\Get(
        operationId: 'getAnalyticDistances',
        summary: 'BONUS : Distances agrégées par code analytique',
        description: 'Retourne la somme des distances parcourues par code analytique sur une période donnée.',
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'groupBy', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['day', 'month', 'year', 'none'], default: 'none')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Agrégations de distances',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'from', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'to', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'groupBy', type: 'string', enum: ['day', 'month', 'year', 'none']),
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'analyticCode', type: 'string'),
                                    new OA\Property(property: 'totalDistanceKm', type: 'number'),
                                    new OA\Property(property: 'group', type: 'string'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Paramètres invalides'),
        ]
    )]
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
