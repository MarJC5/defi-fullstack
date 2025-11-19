<?php

declare(strict_types=1);

namespace App\Application\Command;

class CalculateRouteCommand
{
    public function __construct(
        public readonly string $fromStationId,
        public readonly string $toStationId,
        public readonly string $analyticCode
    ) {
    }
}
