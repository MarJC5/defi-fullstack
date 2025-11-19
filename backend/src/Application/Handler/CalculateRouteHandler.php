<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\CalculateRouteCommand;
use App\Domain\Entity\Route;
use App\Domain\Repository\RouteRepositoryInterface;
use App\Domain\Service\DistancesDataProviderInterface;
use App\Domain\Service\GraphBuilder;
use App\Domain\Service\IdGeneratorInterface;
use App\Domain\Service\RouteCalculator;

class CalculateRouteHandler
{
    private RouteCalculator $calculator;

    public function __construct(
        private readonly GraphBuilder $graphBuilder,
        private readonly RouteRepositoryInterface $repository,
        private readonly IdGeneratorInterface $idGenerator,
        private readonly DistancesDataProviderInterface $distancesDataProvider
    ) {
        $graph = $this->graphBuilder->build($this->distancesDataProvider->getDistancesData());
        $this->calculator = new RouteCalculator($graph, $this->idGenerator);
    }

    public function handle(CalculateRouteCommand $command): Route
    {
        $route = $this->calculator->calculate(
            $command->fromStationId,
            $command->toStationId,
            $command->analyticCode
        );

        $this->repository->save($route);

        return $route;
    }
}
