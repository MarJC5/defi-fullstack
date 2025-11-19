<?php

declare(strict_types=1);

namespace App\Domain\Service;

/**
 * Interface for providing distances data.
 *
 * This is a domain abstraction - the actual implementation
 * (JSON file, database, API, etc.) is in the Infrastructure layer.
 */
interface DistancesDataProviderInterface
{
    /**
     * Get the distances data for building the route graph.
     *
     * @return array Array of rail lines with their stations and distances
     */
    public function getDistancesData(): array;
}
