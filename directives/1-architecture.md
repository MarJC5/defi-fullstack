# Architecture - TDD, DDD & Project Structure

## Project Structure

```
defi-fullstack/
├── backend/                      # Symfony 7 API
│   ├── config/
│   │   ├── packages/
│   │   │   ├── doctrine.yaml
│   │   │   ├── security.yaml
│   │   │   └── lexik_jwt_authentication.yaml
│   │   ├── routes.yaml
│   │   └── services.yaml
│   ├── migrations/               # Doctrine migrations
│   ├── src/
│   │   ├── Domain/               # DDD - Pure business logic (NO framework deps)
│   │   │   ├── Entity/
│   │   │   ├── ValueObject/
│   │   │   ├── Repository/       # Interfaces only
│   │   │   ├── Service/          # Domain services + interfaces
│   │   │   └── Exception/
│   │   ├── Application/          # Use cases / Commands / Queries
│   │   │   ├── Command/
│   │   │   ├── Query/
│   │   │   └── Handler/
│   │   ├── Infrastructure/       # External concerns
│   │   │   ├── Repository/       # Doctrine implementations
│   │   │   ├── Service/          # UuidGenerator, JsonDistancesDataProvider
│   │   │   ├── Exception/        # DataProviderException
│   │   │   └── Persistence/      # Doctrine types & XML mapping
│   │   │       └── Doctrine/
│   │   │           ├── Type/     # Custom types for Value Objects
│   │   │           └── mapping/  # XML ORM mapping
│   │   ├── Controller/           # Symfony controllers
│   │   └── Security/             # JWT authenticator
│   ├── tests/
│   │   ├── Unit/
│   │   └── Integration/
│   ├── public/
│   │   └── index.php
│   ├── composer.json
│   ├── phpunit.xml.dist
│   ├── phpcs.xml
│   ├── phpstan.neon
│   └── Dockerfile
├── frontend/                     # Vue.js 3 + Vuetify 3
│   ├── src/
│   │   ├── assets/
│   │   ├── components/
│   │   ├── composables/
│   │   ├── plugins/
│   │   ├── router/
│   │   ├── services/
│   │   ├── types/
│   │   └── views/
│   ├── tests/
│   │   └── unit/
│   ├── public/
│   ├── index.html
│   ├── package.json
│   ├── tsconfig.json
│   ├── vite.config.ts
│   ├── vitest.config.ts
│   ├── eslint.config.js
│   └── Dockerfile
├── nginx/                        # Reverse proxy
│   ├── nginx.conf
│   └── certs/                    # SSL certificates (gitignored)
│       └── .gitkeep
├── data/                         # Static reference data (shared)
│   ├── stations.json
│   └── distances.json
├── docker/                       # Docker init scripts
│   └── postgres/
│       └── init-test-db.sh       # Creates test database
├── docs/                         # API documentation
│   ├── openapi.yml
│   └── .spectral.yml
├── .github/
│   └── workflows/
│       └── ci.yml
├── docker-compose.yml
├── Makefile                      # Development commands
├── CHANGELOG.md
├── .env.example
├── .gitignore
├── README.md                     # Deployment instructions
└── directives/                   # Development planning (can remove for delivery)
```

---

## TDD - Test Driven Development

### What is TDD?
Development cycle: **Red -> Green -> Refactor**

1. **Red**: Write a failing test first
2. **Green**: Write minimal code to pass the test
3. **Refactor**: Improve code while keeping tests green

### Why TDD for this project?
- **OpenAPI conformity**: Tests ensure API contracts are respected
- **Algorithm correctness**: Routing algorithm must be bulletproof
- **Regression prevention**: Each feature is locked by tests
- **Documentation**: Tests describe expected behavior

### TDD Implementation

#### Backend (PHPUnit)
```php
// 1. RED - Write test first
class RouteCalculatorTest extends TestCase
{
    public function testCalculateDistanceBetweenAdjacentStations(): void
    {
        $calculator = new RouteCalculator($this->graph);

        $route = $calculator->calculate('MX', 'CGE');

        $this->assertEquals(0.65, $route->getDistanceKm());
        $this->assertEquals(['MX', 'CGE'], $route->getPath());
    }
}

// 2. GREEN - Implement minimal code
// 3. REFACTOR - Optimize, extract methods
```

#### Frontend (Vitest)
```typescript
// 1. RED - Write test first
describe('RouteService', () => {
  it('should calculate route between stations', async () => {
    const result = await routeService.calculate('MX', 'ZW', 'ANA-123');

    expect(result.distanceKm).toBeGreaterThan(0);
    expect(result.path).toContain('MX');
    expect(result.path).toContain('ZW');
  });
});
```

### Test Categories

| Type | Purpose | Location | Runner |
|------|---------|----------|--------|
| Unit | Single class/function | `tests/Unit/` | PHPUnit/Vitest |
| Integration | Multiple components | `tests/Integration/` | PHPUnit/Vitest |
| E2E | Full flow | `tests/E2E/` | Cypress (optional) |

---

## DDD - Domain Driven Design

### What is DDD?
Architecture that puts **business logic at the center**, isolated from technical concerns.

### Layers

#### 1. Domain Layer (Core)
Pure business logic. **NO framework dependencies** (no Doctrine ORM, no Symfony).

```php
// Entity - Has identity, uses Value Objects
// NOTE: No Doctrine attributes! Mapping is via XML in Infrastructure
class Route
{
    private string $id;
    private StationId $fromStationId;      // Value Object, not primitive
    private StationId $toStationId;        // Value Object, not primitive
    private string $analyticCode;
    private Distance $distance;             // Value Object, not float
    /** @var StationId[] */
    private array $path;                    // Array of Value Objects
    private DateTimeImmutable $createdAt;
}

// Value Object - No identity, immutable, with behavior
class Distance
{
    private function __construct(private readonly float $kilometers) {}

    public static function fromKilometers(float $km): self
    {
        if ($km < 0) {
            throw new InvalidDistanceException("Distance cannot be negative");
        }
        return new self($km);
    }

    public function add(self $other): self { return new self($this->kilometers + $other->kilometers); }
    public function isZero(): bool { return $this->kilometers === 0.0; }
    public function value(): float { return $this->kilometers; }
}

// Repository Interface
interface RouteRepositoryInterface
{
    public function save(Route $route): void;
    public function findByAnalyticCode(string $code): array;
}

// Service Interfaces (implementations in Infrastructure)
interface IdGeneratorInterface
{
    public function generate(): string;
}

interface DistancesDataProviderInterface
{
    public function getDistancesData(): array;
}

// Domain Service - Business logic
class RouteCalculator
{
    public function __construct(
        private array $graph,
        private IdGeneratorInterface $idGenerator  // Depends on abstraction
    ) {}

    public function calculate(string $from, string $to, string $analyticCode): Route;
}
```

#### 2. Application Layer
Orchestrates use cases. Commands and queries.

```php
// Command
class CalculateRouteCommand
{
    public function __construct(
        public readonly string $fromStationId,
        public readonly string $toStationId,
        public readonly string $analyticCode
    ) {}
}

// Handler - Depends on abstractions only
class CalculateRouteHandler
{
    public function __construct(
        private GraphBuilder $graphBuilder,
        private RouteRepositoryInterface $repository,
        private IdGeneratorInterface $idGenerator,
        private DistancesDataProviderInterface $distancesDataProvider
    ) {
        // Build graph from data provider abstraction
        $graph = $this->graphBuilder->build($this->distancesDataProvider->getDistancesData());
        $this->calculator = new RouteCalculator($graph, $this->idGenerator);
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
```

#### 3. Infrastructure Layer
External concerns: database, HTTP, filesystem. Implements domain interfaces.

```php
// Repository Implementation (uses Doctrine)
class DoctrineRouteRepository implements RouteRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function save(Route $route): void
    {
        $this->entityManager->persist($route);
        $this->entityManager->flush();
    }
}

// Service Implementations
class UuidGenerator implements IdGeneratorInterface
{
    public function generate(): string
    {
        return Uuid::v4()->toRfc4122();
    }
}

class JsonDistancesDataProvider implements DistancesDataProviderInterface
{
    public function __construct(private string $distancesPath) {}

    public function getDistancesData(): array
    {
        $json = file_get_contents($this->distancesPath);
        if ($json === false) {
            throw DataProviderException::cannotReadFile($this->distancesPath);
        }
        return json_decode($json, true);
    }
}

// Custom Doctrine Types for Value Objects
class StationIdType extends StringType
{
    public function convertToPHPValue($value, $platform): ?StationId
    {
        return $value ? StationId::fromString($value) : null;
    }

    public function convertToDatabaseValue($value, $platform): ?string
    {
        return $value instanceof StationId ? $value->value() : $value;
    }
}

// XML ORM Mapping (in Infrastructure/Persistence/Doctrine/mapping/Route.orm.xml)
// Keeps Domain entity free of Doctrine attributes
```

### Why DDD for this project?

1. **Testability**: Domain is pure PHP, easy to unit test
2. **Flexibility**: Can swap database (Postgres -> MariaDB) without touching business logic
3. **OpenAPI alignment**: Domain models map directly to API schemas
4. **Maintainability**: Clear boundaries between concerns

---

## Domain Model for Train Routing

### Entities
- **Route**: A calculated journey with id, stations, distance, path, analyticCode
- **Station**: A stop on the network (id, shortName, longName)

### Value Objects
- **StationId**: Validated station identifier
- **Distance**: Non-negative kilometers
- **AnalyticCode**: Business categorization

### Domain Services
- **RouteCalculator**: Pathfinding algorithm (Dijkstra)
- **GraphBuilder**: Builds network from distances.json

### Aggregates
- **Route** is the aggregate root (persisted as unit)

---

## Implementation Strategy

### Phase 1: Domain First
1. Write tests for RouteCalculator
2. Implement graph structure
3. Implement Dijkstra algorithm
4. Test edge cases (no route, same station, cross-line)

### Phase 2: Application Layer
1. Create commands/handlers
2. Wire up dependency injection
3. Add validation

### Phase 3: Infrastructure
1. Controllers matching OpenAPI
2. Database persistence (optional)
3. JWT authentication

### Phase 4: Frontend
1. API service layer
2. Components with TypeScript types
3. Tests with mocked API

---

## Testing Strategy with TDD + DDD

```
Unit Tests (80%+)
├── Domain/
│   ├── RouteCalculatorTest
│   ├── StationIdTest
│   └── DistanceTest
└── Application/
    └── CalculateRouteHandlerTest

Integration Tests
├── Repository tests (real DB)
└── API endpoint tests (real HTTP)
```

### Coverage Targets
- Backend: 80% minimum
- Frontend: 70% minimum
- Critical paths (routing algorithm): 100%
