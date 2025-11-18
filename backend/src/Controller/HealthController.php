<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class HealthController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    #[Route('/health', name: 'api_health', methods: ['GET'])]
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
