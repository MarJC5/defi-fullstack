<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Distance;
use App\Domain\ValueObject\StationId;

/**
 * Route aggregate root - pure domain entity.
 *
 * Database mapping is handled separately in Infrastructure layer
 * via XML mapping files (see src/Infrastructure/Persistence/Doctrine/mapping/).
 */
class Route
{
    private string $id;
    private StationId $fromStationId;
    private StationId $toStationId;
    private string $analyticCode;
    private Distance $distance;
    /** @var StationId[] */
    private array $path;
    private \DateTimeImmutable $createdAt;

    /**
     * @param StationId[] $path
     */
    public function __construct(
        string $id,
        StationId $fromStationId,
        StationId $toStationId,
        string $analyticCode,
        Distance $distance,
        array $path,
        ?\DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->fromStationId = $fromStationId;
        $this->toStationId = $toStationId;
        $this->analyticCode = $analyticCode;
        $this->distance = $distance;
        $this->path = $path;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFromStationId(): StationId
    {
        return $this->fromStationId;
    }

    public function getToStationId(): StationId
    {
        return $this->toStationId;
    }

    public function getAnalyticCode(): string
    {
        return $this->analyticCode;
    }

    public function getDistance(): Distance
    {
        return $this->distance;
    }

    public function getDistanceKm(): float
    {
        return $this->distance->kilometers();
    }

    /**
     * @return string[]
     */
    public function getPath(): array
    {
        return array_map(fn(StationId $s) => $s->value(), $this->path);
    }

    /**
     * @return StationId[]
     */
    public function getPathStations(): array
    {
        return $this->path;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fromStationId' => $this->fromStationId->value(),
            'toStationId' => $this->toStationId->value(),
            'analyticCode' => $this->analyticCode,
            'distanceKm' => $this->distance->kilometers(),
            'path' => $this->getPath(),
            'createdAt' => $this->createdAt->format('c'),
        ];
    }
}
