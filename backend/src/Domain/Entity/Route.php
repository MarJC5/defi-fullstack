<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'routes')]
#[ORM\Index(columns: ['analytic_code'], name: 'idx_routes_analytic_code')]
#[ORM\Index(columns: ['created_at'], name: 'idx_routes_created_at')]
class Route
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'from_station_id', type: 'string', length: 10)]
    private string $fromStationId;

    #[ORM\Column(name: 'to_station_id', type: 'string', length: 10)]
    private string $toStationId;

    #[ORM\Column(name: 'analytic_code', type: 'string', length: 50)]
    private string $analyticCode;

    #[ORM\Column(name: 'distance_km', type: 'float')]
    private float $distanceKm;

    #[ORM\Column(type: 'json')]
    private array $path;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
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
