<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\CalculateRouteCommand;
use App\Domain\Entity\Route;
use App\Domain\Repository\RouteRepositoryInterface;
use App\Domain\Service\GraphBuilder;
use App\Domain\Service\RouteCalculator;

class CalculateRouteHandler
{
    private RouteCalculator $calculator;

    public function __construct(
        private readonly GraphBuilder $graphBuilder,
        private readonly RouteRepositoryInterface $repository,
        string $distancesPath
    ) {
        $json = file_get_contents($distancesPath);
        if ($json === false) {
            throw new \RuntimeException("Cannot read distances file: $distancesPath");
        }
        $distancesData = json_decode($json, true);
        $graph = $this->graphBuilder->build($distancesData);
        $this->calculator = new RouteCalculator($graph);
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
