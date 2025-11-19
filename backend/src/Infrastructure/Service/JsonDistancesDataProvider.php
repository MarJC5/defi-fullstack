<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Service\DistancesDataProviderInterface;
use App\Infrastructure\Exception\DataProviderException;

/**
 * JSON file implementation of DistancesDataProviderInterface.
 *
 * Reads distances data from a JSON file on the filesystem.
 */
class JsonDistancesDataProvider implements DistancesDataProviderInterface
{
    private ?array $cachedData = null;

    public function __construct(
        private readonly string $distancesPath
    ) {
    }

    public function getDistancesData(): array
    {
        // Cache the data to avoid reading the file multiple times
        if ($this->cachedData !== null) {
            return $this->cachedData;
        }

        $json = file_get_contents($this->distancesPath);
        if ($json === false) {
            throw DataProviderException::cannotReadFile($this->distancesPath);
        }

        $data = json_decode($json, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw DataProviderException::invalidJson($this->distancesPath, json_last_error_msg());
        }

        $this->cachedData = $data;

        return $this->cachedData;
    }
}
