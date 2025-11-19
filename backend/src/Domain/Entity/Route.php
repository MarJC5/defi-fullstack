<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Symfony\Component\Uid\Uuid;

class Route
{
    private string $id;
    private string $fromStationId;
    private string $toStationId;
    private string $analyticCode;
    private float $distanceKm;
    private array $path;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $fromStationId,
        string $toStationId,
        string $analyticCode,
        float $distanceKm,
        array $path
    ) {
        $this->id = Uuid::v4()->toRfc4122();
        $this->fromStationId = $fromStationId;
        $this->toStationId = $toStationId;
        $this->analyticCode = $analyticCode;
        $this->distanceKm = $distanceKm;
        $this->path = $path;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFromStationId(): string
    {
        return $this->fromStationId;
    }

    public function getToStationId(): string
    {
        return $this->toStationId;
    }

    public function getAnalyticCode(): string
    {
        return $this->analyticCode;
    }

    public function getDistanceKm(): float
    {
        return $this->distanceKm;
    }

    public function getPath(): array
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
            'fromStationId' => $this->fromStationId,
            'toStationId' => $this->toStationId,
            'analyticCode' => $this->analyticCode,
            'distanceKm' => $this->distanceKm,
            'path' => $this->path,
            'createdAt' => $this->createdAt->format('c'),
        ];
    }
}
