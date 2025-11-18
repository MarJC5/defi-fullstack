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

## Doctrine Entity

```php
// src/Domain/Entity/Route.php
namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\DoctrineRouteRepository;

#[ORM\Entity(repositoryClass: DoctrineRouteRepository::class)]
#[ORM\Table(name: 'routes')]
#[ORM\Index(columns: ['analytic_code'], name: 'idx_routes_analytic_code')]
#[ORM\Index(columns: ['created_at'], name: 'idx_routes_created_at')]
#[ORM\Index(columns: ['analytic_code', 'created_at'], name: 'idx_routes_analytic_created')]
class Route
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'from_station_id', type: 'string', length: 10)]
    private string $fromStationId;

    #[ORM\Column(name: 'to_station_id', type: 'string', length: 10)]
    private string $toStationId;

    #[ORM\Column(name: 'analytic_code', type: 'string', length: 50)]
    private string $analyticCode;

    #[ORM\Column(name: 'distance_km', type: 'decimal', precision: 10, scale: 2)]
    private float $distanceKm;

    #[ORM\Column(type: 'json')]
    private array $path;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $fromStationId,
        string $toStationId,
        string $analyticCode,
        float $distanceKm,
        array $path,
        \DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->fromStationId = $fromStationId;
        $this->toStationId = $toStationId;
        $this->analyticCode = $analyticCode;
        $this->distanceKm = $distanceKm;
        $this->path = $path;
        $this->createdAt = $createdAt;
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getFromStationId(): string { return $this->fromStationId; }
    public function getToStationId(): string { return $this->toStationId; }
    public function getAnalyticCode(): string { return $this->analyticCode; }
    public function getDistanceKm(): float { return $this->distanceKm; }
    public function getPath(): array { return $this->path; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fromStationId' => $this->fromStationId,
            'toStationId' => $this->toStationId,
            'analyticCode' => $this->analyticCode,
            'distanceKm' => (float) $this->distanceKm,
            'path' => $this->path,
            'createdAt' => $this->createdAt->format('c'),
        ];
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

    orm:
        auto_generate_proxy_classes: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            App:
                is_bundle: false
                dir: '%kernel.project_dir%/src/Domain/Entity'
                prefix: 'App\Domain\Entity'
                alias: App
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

```
backend/
├── data/
│   ├── stations.json      # Copy from project root
│   └── distances.json     # Copy from project root
```

### API Endpoint for Stations (Optional)

```php
// src/Controller/StationController.php
namespace App\Controller;

use App\Domain\Service\StationLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class StationController extends AbstractController
{
    public function __construct(
        private StationLoader $stationLoader
    ) {}

    #[Route('/stations', name: 'list_stations', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->stationLoader->getStations());
    }
}
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
