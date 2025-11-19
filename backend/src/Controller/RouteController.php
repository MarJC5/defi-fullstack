<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Command\CalculateRouteCommand;
use App\Application\Handler\CalculateRouteHandler;
use App\Domain\Exception\NoRouteFoundException;
use App\Domain\Exception\StationNotFoundException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
#[OA\Tag(name: 'Routing')]
class RouteController extends AbstractController
{
    public function __construct(
        private readonly CalculateRouteHandler $handler,
    ) {
    }

    #[Route('/routes', name: 'create_route', methods: ['POST'])]
    #[OA\Post(
        operationId: 'calculerRoute',
        summary: 'Calculer un trajet A vers B',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['fromStationId', 'toStationId', 'analyticCode'],
                properties: [
                    new OA\Property(property: 'fromStationId', type: 'string', example: 'MX'),
                    new OA\Property(property: 'toStationId', type: 'string', example: 'ZW'),
                    new OA\Property(property: 'analyticCode', type: 'string', example: 'ANA-123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Trajet calculé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string'),
                        new OA\Property(property: 'fromStationId', type: 'string'),
                        new OA\Property(property: 'toStationId', type: 'string'),
                        new OA\Property(property: 'analyticCode', type: 'string'),
                        new OA\Property(property: 'distanceKm', type: 'number'),
                        new OA\Property(property: 'path', type: 'array', items: new OA\Items(type: 'string')),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Requête invalide'),
            new OA\Response(response: 422, description: 'Données non valides'),
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $content = $request->getContent();

        if (empty($content)) {
            return $this->json([
                'message' => 'Validation failed',
                'details' => ['Request body is required'],
            ], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($content, true);

        if ($data === null) {
            return $this->json([
                'message' => 'Validation failed',
                'details' => ['Invalid JSON body'],
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validation
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return $this->json([
                'message' => 'Validation failed',
                'details' => $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new CalculateRouteCommand(
                $data['fromStationId'],
                $data['toStationId'],
                $data['analyticCode']
            );

            $route = $this->handler->handle($command);

            return $this->json($route->toArray(), Response::HTTP_CREATED);
        } catch (StationNotFoundException $e) {
            return $this->json([
                'code' => 'STATION_NOT_FOUND',
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (NoRouteFoundException $e) {
            return $this->json([
                'code' => 'NO_ROUTE_FOUND',
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param array<string, mixed>|null $data
     * @return array<string>
     */
    private function validate(?array $data): array
    {
        $errors = [];

        if (empty($data['fromStationId'])) {
            $errors[] = 'fromStationId is required';
        }
        if (empty($data['toStationId'])) {
            $errors[] = 'toStationId is required';
        }
        if (empty($data['analyticCode'])) {
            $errors[] = 'analyticCode is required';
        }

        return $errors;
    }
}
