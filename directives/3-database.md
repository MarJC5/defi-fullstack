# Database - PostgreSQL Schema & Doctrine

## Tech Stack

- **PostgreSQL 16** (their production DB)
- **Doctrine ORM 3** (Symfony integration)
- **Doctrine Migrations** (schema versioning)

---

## Database Schema

### ERD (Entity Relationship Diagram)

```
┌─────────────────────────────────┐
│            routes               │
├─────────────────────────────────┤
│ id              UUID PK         │
│ from_station_id VARCHAR(10)     │
│ to_station_id   VARCHAR(10)     │
│ analytic_code   VARCHAR(50)     │
│ distance_km     DECIMAL(10,2)   │
│ path            JSON            │
│ created_at      TIMESTAMP       │
├─────────────────────────────────┤
│ INDEX idx_analytic_code         │
│ INDEX idx_created_at            │
│ INDEX idx_analytic_created      │
└─────────────────────────────────┘
```

### SQL Schema

```sql
-- migrations/Version20250101000000.php (generated SQL)

CREATE TABLE routes (
    id UUID NOT NULL,
    from_station_id VARCHAR(10) NOT NULL,
    to_station_id VARCHAR(10) NOT NULL,
    analytic_code VARCHAR(50) NOT NULL,
    distance_km DECIMAL(10, 2) NOT NULL,
    path JSON NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

-- Indexes for stats queries
CREATE INDEX idx_routes_analytic_code ON routes (analytic_code);
CREATE INDEX idx_routes_created_at ON routes (created_at);
CREATE INDEX idx_routes_analytic_created ON routes (analytic_code, created_at);

COMMENT ON COLUMN routes.path IS 'Ordered array of station IDs';
```

---

## Domain Entity (Pure PHP - No Doctrine)

The Domain entity uses Value Objects and has **NO Doctrine attributes** - mapping is done via XML in Infrastructure layer.

```php
// src/Domain/Entity/Route.php
namespace App\Domain\Entity;

use App\Domain\ValueObject\StationId;
use App\Domain\ValueObject\Distance;

// NOTE: No Doctrine ORM attributes! Mapping is via XML in Infrastructure layer
class Route
{
    private string $id;
    private StationId $fromStationId;      // Value Object
    private StationId $toStationId;        // Value Object
    private string $analyticCode;
    private Distance $distance;             // Value Object
    /** @var StationId[] */
    private array $path;                    // Array of Value Objects
    private \DateTimeImmutable $createdAt;

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

    // Getters return Value Objects
    public function getId(): string { return $this->id; }
    public function getFromStationId(): StationId { return $this->fromStationId; }
    public function getToStationId(): StationId { return $this->toStationId; }
    public function getAnalyticCode(): string { return $this->analyticCode; }
    public function getDistance(): Distance { return $this->distance; }
    public function getDistanceKm(): float { return $this->distance->value(); }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return string[] Station IDs as strings */
    public function getPath(): array
    {
        return array_map(fn(StationId $s) => $s->value(), $this->path);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fromStationId' => $this->fromStationId->value(),
            'toStationId' => $this->toStationId->value(),
            'analyticCode' => $this->analyticCode,
            'distanceKm' => $this->distance->value(),
            'path' => $this->getPath(),
            'createdAt' => $this->createdAt->format('c'),
        ];
    }
}
```

---

## XML ORM Mapping (Infrastructure Layer)

Mapping is separated from the Domain entity to keep it framework-agnostic.

```xml
<!-- src/Infrastructure/Persistence/Doctrine/mapping/Route.orm.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping">
    <entity name="App\Domain\Entity\Route" table="routes">
        <id name="id" type="string" length="36"/>

        <!-- Custom types for Value Objects -->
        <field name="fromStationId" column="from_station_id" type="station_id" length="10"/>
        <field name="toStationId" column="to_station_id" type="station_id" length="10"/>
        <field name="analyticCode" column="analytic_code" type="string" length="50"/>
        <field name="distance" column="distance_km" type="distance"/>
        <field name="path" type="station_id_array"/>
        <field name="createdAt" column="created_at" type="datetime_immutable"/>

        <indexes>
            <index name="idx_routes_analytic_code" columns="analytic_code"/>
            <index name="idx_routes_created_at" columns="created_at"/>
            <index name="idx_routes_analytic_created" columns="analytic_code,created_at"/>
        </indexes>
    </entity>
</doctrine-mapping>
```

---

## Custom Doctrine Types for Value Objects

Custom types handle conversion between database primitives and domain Value Objects transparently.

```php
// src/Infrastructure/Persistence/Doctrine/Type/StationIdType.php
namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\StationId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class StationIdType extends StringType
{
    public const NAME = 'station_id';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?StationId
    {
        if ($value === null || $value === '') {
            return null;
        }
        return StationId::fromString($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof StationId ? $value->value() : $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

// src/Infrastructure/Persistence/Doctrine/Type/DistanceType.php
class DistanceType extends Type
{
    public const NAME = 'distance';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Distance
    {
        if ($value === null) {
            return null;
        }
        return Distance::fromKilometers((float) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?float
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof Distance ? $value->value() : (float) $value;
    }
}

// src/Infrastructure/Persistence/Doctrine/Type/StationIdArrayType.php
class StationIdArrayType extends JsonType
{
    public const NAME = 'station_id_array';

    public function convertToPHPValue($value, AbstractPlatform $platform): array
    {
        $array = parent::convertToPHPValue($value, $platform);
        return array_map(fn($id) => StationId::fromString($id), $array ?? []);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        $primitives = array_map(
            fn($item) => $item instanceof StationId ? $item->value() : $item,
            $value
        );
        return parent::convertToDatabaseValue($primitives, $platform);
    }
}
```

---

## Doctrine Configuration

### config/packages/doctrine.yaml

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        driver: 'pdo_pgsql'
        charset: utf8
        # Register custom types for Value Objects
        types:
            station_id: App\Infrastructure\Persistence\Doctrine\Type\StationIdType
            distance: App\Infrastructure\Persistence\Doctrine\Type\DistanceType
            station_id_array: App\Infrastructure\Persistence\Doctrine\Type\StationIdArrayType

    orm:
        auto_generate_proxy_classes: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            # Use XML mapping from Infrastructure layer
            Domain:
                type: xml
                is_bundle: false
                dir: '%kernel.project_dir%/src/Infrastructure/Persistence/Doctrine/mapping'
                prefix: 'App\Domain\Entity'
                alias: Domain
```

### .env

```bash
DATABASE_URL="postgresql://app:secret@db:5432/trainrouting?serverVersion=16&charset=utf8"
```

---

## Migrations

### Setup

```bash
# Install Doctrine migrations
composer require doctrine/doctrine-migrations-bundle

# Generate migration from entity
php bin/console doctrine:migrations:diff

# Run migrations
php bin/console doctrine:migrations:migrate

# Check status
php bin/console doctrine:migrations:status
```

### Initial Migration

```php
// migrations/Version20250101000000.php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create routes table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE routes (
            id UUID NOT NULL,
            from_station_id VARCHAR(10) NOT NULL,
            to_station_id VARCHAR(10) NOT NULL,
            analytic_code VARCHAR(50) NOT NULL,
            distance_km DECIMAL(10, 2) NOT NULL,
            path JSON NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE INDEX idx_routes_analytic_code ON routes (analytic_code)');
        $this->addSql('CREATE INDEX idx_routes_created_at ON routes (created_at)');
        $this->addSql('CREATE INDEX idx_routes_analytic_created ON routes (analytic_code, created_at)');

        $this->addSql('COMMENT ON COLUMN routes.id IS \'(DC2Type:guid)\'');
        $this->addSql('COMMENT ON COLUMN routes.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE routes');
    }
}
```

---

## Stations Data

Stations are **static reference data** - loaded from JSON files, not stored in DB.

### Loading Strategy

```php
// src/Domain/Service/StationLoader.php
namespace App\Domain\Service;

class StationLoader
{
    private array $stations = [];
    private string $dataPath;

    public function __construct(string $projectDir)
    {
        $this->dataPath = $projectDir . '/data';
    }

    public function getStations(): array
    {
        if (empty($this->stations)) {
            $json = file_get_contents($this->dataPath . '/stations.json');
            $this->stations = json_decode($json, true);
        }
        return $this->stations;
    }

    public function getStationByShortName(string $shortName): ?array
    {
        foreach ($this->getStations() as $station) {
            if ($station['shortName'] === $shortName) {
                return $station;
            }
        }
        return null;
    }

    public function validateStation(string $shortName): bool
    {
        return $this->getStationByShortName($shortName) !== null;
    }
}
```

### Service Registration

```yaml
# config/services.yaml
services:
    App\Domain\Service\StationLoader:
        arguments:
            $projectDir: '%kernel.project_dir%'
```

### Data Files Location

Data files are at the **project root** (shared between backend and frontend):

```
defi-fullstack/
├── data/
│   ├── stations.json
│   └── distances.json
```

In Docker, this is mounted to `/data`:
```yaml
volumes:
  - ./data:/data:ro
```

---

## Docker Integration

### Auto-run Migrations

```yaml
# docker-compose.yml (backend service)
backend:
    build:
        context: ./backend
    command: >
        sh -c "php bin/console doctrine:migrations:migrate --no-interaction &&
               php-fpm"
    depends_on:
        db:
            condition: service_healthy
```

### Health Check for DB

```yaml
db:
    image: postgres:16-alpine
    healthcheck:
        test: ["CMD-SHELL", "pg_isready -U app -d trainrouting"]
        interval: 5s
        timeout: 5s
        retries: 5
```

---

## Performance Indexes

| Index | Purpose | Query Pattern |
|-------|---------|---------------|
| `idx_analytic_code` | Filter by code | `WHERE analytic_code = ?` |
| `idx_created_at` | Date range filter | `WHERE created_at BETWEEN ? AND ?` |
| `idx_analytic_created` | Stats grouping | `GROUP BY analytic_code` with date filter |

### Query Examples

```sql
-- Stats by analytic code (uses idx_analytic_created)
SELECT
    analytic_code,
    SUM(distance_km) as total_distance
FROM routes
WHERE created_at BETWEEN '2025-01-01' AND '2025-12-31'
GROUP BY analytic_code;

-- Monthly breakdown (uses idx_analytic_created)
SELECT
    analytic_code,
    TO_CHAR(created_at, 'YYYY-MM') as month,
    SUM(distance_km) as total_distance
FROM routes
WHERE created_at BETWEEN '2025-01-01' AND '2025-12-31'
GROUP BY analytic_code, TO_CHAR(created_at, 'YYYY-MM')
ORDER BY month;
```

---

## Testing with Database

### Test Configuration

```yaml
# config/packages/test/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        # Use same DB or separate test DB
```

### Integration Test Example

```php
// tests/Integration/RouteRepositoryTest.php
namespace Tests\Integration;

use App\Domain\Entity\Route;
use App\Repository\DoctrineRouteRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RouteRepositoryTest extends KernelTestCase
{
    private DoctrineRouteRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(DoctrineRouteRepository::class);
    }

    public function testSaveAndRetrieveRoute(): void
    {
        $route = new Route(
            id: 'test-123',
            fromStationId: 'MX',
            toStationId: 'ZW',
            analyticCode: 'TEST-001',
            distanceKm: 62.08,
            path: ['MX', 'CGE', 'ZW'],
            createdAt: new \DateTimeImmutable()
        );

        $this->repository->save($route);

        $found = $this->repository->findById('test-123');
        $this->assertNotNull($found);
        $this->assertEquals('MX', $found->getFromStationId());
    }
}
```

---

## Why No Stations Table?

1. **Static data** - Stations don't change during runtime
2. **Simplicity** - No foreign keys, no joins needed
3. **Performance** - JSON in memory is faster than DB lookup
4. **Challenge scope** - Focus on routing logic, not CRUD

If needed later, stations could be migrated to DB with:
```sql
CREATE TABLE stations (
    id SERIAL PRIMARY KEY,
    short_name VARCHAR(10) UNIQUE NOT NULL,
    long_name VARCHAR(100) NOT NULL
);
```
