<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class StationNotFoundException extends \DomainException
{
    public function __construct(string $stationId)
    {
        parent::__construct(sprintf("Station '%s' not found", $stationId));
    }
}
