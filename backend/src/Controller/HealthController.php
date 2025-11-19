<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
#[OA\Tag(name: 'Health')]
class HealthController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    #[Route('/health', name: 'api_health', methods: ['GET'])]
    #[OA\Get(
        operationId: 'healthCheck',
        summary: 'Health check endpoint',
        security: [],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service health status',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'OK'),
                        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'service', type: 'string', example: 'trainrouting-backend'),
                        new OA\Property(property: 'database', type: 'string', example: 'OK'),
                    ]
                )
            ),
        ]
    )]
    public function health(): JsonResponse
    {
        $dbStatus = 'OK';

        try {
            $this->connection->executeQuery('SELECT 1');
        } catch (\Exception $e) {
            $dbStatus = 'ERROR: ' . $e->getMessage();
        }

        return $this->json([
            'status' => $dbStatus === 'OK' ? 'OK' : 'DEGRADED',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'service' => 'trainrouting-backend',
            'database' => $dbStatus,
        ]);
    }
}
