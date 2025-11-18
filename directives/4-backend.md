# Backend - PHP 8.4 API Implementation

## Tech Stack

- **PHP 8.4** (required)
- **Symfony 7** (their production framework)
- **PHPUnit** (tests + coverage)
- **PHPCS** (linting)
- **PHPStan** (static analysis)

> They use Symfony 7 and CakePHP 5 in production - Symfony recommended

---

## Project Setup

### Directory Structure (Symfony 7)

```
backend/
├── config/
│   ├── packages/
│   ├── routes.yaml
│   └── services.yaml
├── src/
│   ├── Domain/
│   │   ├── Entity/
│   │   │   └── Route.php
│   │   ├── ValueObject/
│   │   │   ├── StationId.php
│   │   │   └── Distance.php
│   │   ├── Repository/
│   │   │   └── RouteRepositoryInterface.php
│   │   ├── Service/
│   │   │   ├── GraphBuilder.php
│   │   │   └── RouteCalculator.php
│   │   └── Exception/
│   │       ├── StationNotFoundException.php
│   │       └── NoRouteFoundException.php
│   ├── Application/
│   │   ├── Command/
│   │   │   └── CalculateRouteCommand.php
│   │   ├── Query/
│   │   │   └── GetAnalyticDistancesQuery.php
│   │   └── Handler/
│   │       ├── CalculateRouteHandler.php
│   │       └── GetAnalyticDistancesHandler.php
│   ├── Controller/
│   │   ├── RouteController.php
│   │   └── StatsController.php
│   ├── Security/
│   │   └── JwtAuthenticator.php
│   └── Repository/
│       └── DoctrineRouteRepository.php
├── tests/
│   ├── Unit/
│   │   ├── Domain/
│   │   │   ├── RouteCalculatorTest.php
│   │   │   ├── GraphBuilderTest.php
│   │   │   └── StationIdTest.php
│   │   └── Application/
│   │       └── CalculateRouteHandlerTest.php
│   └── Integration/
│       ├── RouteControllerTest.php
│       └── StatsControllerTest.php
├── public/
│   └── index.php
├── composer.json
├── phpunit.xml.dist
├── phpcs.xml
├── phpstan.neon
└── Dockerfile
```

### composer.json

```json
{
  "name": "defi/train-routing",
  "type": "project",
  "require": {
    "php": "^8.4",
    "symfony/framework-bundle": "^7.0",
    "symfony/yaml": "^7.0",
    "symfony/dotenv": "^7.0",
    "symfony/runtime": "^7.0",
    "doctrine/orm": "^3.0",
    "doctrine/doctrine-bundle": "^2.0",
    "doctrine/doctrine-migrations-bundle": "^3.0",
    "lexik/jwt-authentication-bundle": "^3.0",
    "nelmio/api-doc-bundle": "^4.0",
    "nelmio/cors-bundle": "^2.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.0",
    "symfony/test-pack": "^1.0",
    "squizlabs/php_codesniffer": "^3.0",
    "phpstan/phpstan": "^1.0",
    "phpstan/phpstan-symfony": "^1.0"
  },
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Tests\\": "tests/"
    }
  }
}
```

---

## TDD Workflow

### Red-Green-Refactor Cycle

For each feature:

1. **Write test first** (it will fail)
2. **Write minimal code** to pass
3. **Refactor** while keeping tests green
4. **Commit** after each green state

### Example: Route Calculator

#### Step 1: RED - Write failing test

```php
// tests/Unit/Domain/RouteCalculatorTest.php
namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use App\Domain\Service\RouteCalculator;
use App\Domain\Service\GraphBuilder;

class RouteCalculatorTest extends TestCase
{
    private RouteCalculator $calculator;

    protected function setUp(): void
    {
        $graph = GraphBuilder::fromJsonFile(__DIR__ . '/../../../distances.json');
        $this->calculator = new RouteCalculator($graph);
    }

    public function testCalculateAdjacentStations(): void
    {
        $route = $this->calculator->calculate('MX', 'CGE', 'TEST-001');

        $this->assertEquals(0.65, $route->getDistanceKm());
        $this->assertEquals(['MX', 'CGE'], $route->getPath());
    }

    public function testCalculateMultipleStations(): void
    {
        $route = $this->calculator->calculate('MX', 'CABY', 'TEST-002');

        $this->assertGreaterThan(0, $route->getDistanceKm());
        $this->assertEquals('MX', $route->getPath()[0]);
        $this->assertEquals('CABY', $route->getPath()[count($route->getPath()) - 1]);
    }

    public function testCalculateCrossLineRoute(): void
    {
        // MX (MOB) to VV (MVR-ce) - must go through CABY
        $route = $this->calculator->calculate('MX', 'VV', 'TEST-003');

        $this->assertContains('CABY', $route->getPath());
    }

    public function testThrowsExceptionForUnknownStation(): void
    {
        $this->expectException(\App\Domain\Exception\StationNotFoundException::class);

        $this->calculator->calculate('UNKNOWN', 'MX', 'TEST-004');
    }

    public function testThrowsExceptionForNoRoute(): void
    {
        // If network is disconnected
        $this->expectException(\App\Domain\Exception\NoRouteFoundException::class);

        // This would only happen with bad data
        $this->calculator->calculate('ISOLATED', 'MX', 'TEST-005');
    }

    public function testSameStationReturnsZeroDistance(): void
    {
        $route = $this->calculator->calculate('MX', 'MX', 'TEST-006');

        $this->assertEquals(0, $route->getDistanceKm());
        $this->assertEquals(['MX'], $route->getPath());
    }
}
```

#### Step 2: GREEN - Implement

```php
// src/Domain/Service/RouteCalculator.php
namespace App\Domain\Service;

use App\Domain\Entity\Route;
use App\Domain\Exception\StationNotFoundException;
use App\Domain\Exception\NoRouteFoundException;

class RouteCalculator
{
    public function __construct(
        private array $graph
    ) {}

    public function calculate(string $from, string $to, string $analyticCode): Route
    {
        if (!isset($this->graph[$from])) {
            throw new StationNotFoundException("Station '$from' not found");
        }
        if (!isset($this->graph[$to])) {
            throw new StationNotFoundException("Station '$to' not found");
        }

        if ($from === $to) {
            return new Route(
                id: $this->generateId(),
                fromStationId: $from,
                toStationId: $to,
                analyticCode: $analyticCode,
                distanceKm: 0,
                path: [$from],
                createdAt: new \DateTimeImmutable()
            );
        }

        // Dijkstra's algorithm
        $distances = [];
        $previous = [];
        $queue = new \SplPriorityQueue();

        foreach (array_keys($this->graph) as $station) {
            $distances[$station] = PHP_INT_MAX;
            $previous[$station] = null;
        }

        $distances[$from] = 0;
        $queue->insert($from, 0);

        while (!$queue->isEmpty()) {
            $current = $queue->extract();

            if ($current === $to) {
                break;
            }

            if (!isset($this->graph[$current])) {
                continue;
            }

            foreach ($this->graph[$current] as $neighbor => $distance) {
                $alt = $distances[$current] + $distance;

                if ($alt < $distances[$neighbor]) {
                    $distances[$neighbor] = $alt;
                    $previous[$neighbor] = $current;
                    $queue->insert($neighbor, -$alt);
                }
            }
        }

        if ($distances[$to] === PHP_INT_MAX) {
            throw new NoRouteFoundException("No route from '$from' to '$to'");
        }

        // Reconstruct path
        $path = [];
        $current = $to;
        while ($current !== null) {
            array_unshift($path, $current);
            $current = $previous[$current];
        }

        return new Route(
            id: $this->generateId(),
            fromStationId: $from,
            toStationId: $to,
            analyticCode: $analyticCode,
            distanceKm: round($distances[$to], 2),
            path: $path,
            createdAt: new \DateTimeImmutable()
        );
    }

    private function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
```

#### Step 3: REFACTOR

- Extract Dijkstra to separate method
- Add caching for repeated calculations
- Improve error messages

---

## DDD Implementation

### Domain Layer

#### Entity

```php
// src/Domain/Entity/Route.php
namespace App\Domain\Entity;

class Route
{
    public function __construct(
        private readonly string $id,
        private readonly string $fromStationId,
        private readonly string $toStationId,
        private readonly string $analyticCode,
        private readonly float $distanceKm,
        private readonly array $path,
        private readonly \DateTimeImmutable $createdAt
    ) {}

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
            'distanceKm' => $this->distanceKm,
            'path' => $this->path,
            'createdAt' => $this->createdAt->format('c'),
        ];
    }
}
```

#### Value Objects

```php
// src/Domain/ValueObject/StationId.php
namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidStationIdException;

class StationId
{
    public function __construct(
        private readonly string $value
    ) {
        if (empty(trim($value))) {
            throw new InvalidStationIdException('Station ID cannot be empty');
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(StationId $other): bool
    {
        return $this->value === $other->value;
    }
}
```

#### Repository Interface

```php
// src/Domain/Repository/RouteRepositoryInterface.php
namespace App\Domain\Repository;

use App\Domain\Entity\Route;

interface RouteRepositoryInterface
{
    public function save(Route $route): void;
    public function findById(string $id): ?Route;
    public function findByAnalyticCode(string $code, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): array;
    public function getDistancesByAnalyticCode(?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null, string $groupBy = 'none'): array;
}
```

### Application Layer

```php
// src/Application/Command/CalculateRouteCommand.php
namespace App\Application\Command;

class CalculateRouteCommand
{
    public function __construct(
        public readonly string $fromStationId,
        public readonly string $toStationId,
        public readonly string $analyticCode
    ) {}
}

// src/Application/Handler/CalculateRouteHandler.php
namespace App\Application\Handler;

use App\Application\Command\CalculateRouteCommand;
use App\Domain\Entity\Route;
use App\Domain\Service\RouteCalculator;
use App\Domain\Repository\RouteRepositoryInterface;

class CalculateRouteHandler
{
    public function __construct(
        private RouteCalculator $calculator,
        private RouteRepositoryInterface $repository
    ) {}

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

### Infrastructure Layer

#### Controller (Symfony)

```php
// src/Controller/RouteController.php
namespace App\Controller;

use App\Application\Command\CalculateRouteCommand;
use App\Application\Handler\CalculateRouteHandler;
use App\Domain\Exception\StationNotFoundException;
use App\Domain\Exception\NoRouteFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1')]
class RouteController extends AbstractController
{
    public function __construct(
        private CalculateRouteHandler $handler,
        private ValidatorInterface $validator
    ) {}

    #[Route('/routes', name: 'create_route', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return $this->json([
                'message' => 'Validation failed',
                'details' => $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new CalculateRouteCommand(
                $data['fromStationId'],
                $data['toStationId'],
                $data['analyticCode']
            );

            $route = $this->handler->handle($command);

            return $this->json($route->toArray(), Response::HTTP_CREATED);

        } catch (StationNotFoundException $e) {
            return $this->json([
                'code' => 'STATION_NOT_FOUND',
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (NoRouteFoundException $e) {
            return $this->json([
                'code' => 'NO_ROUTE_FOUND',
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    private function validate(?array $data): array
    {
        $errors = [];

        if (!$data) {
            return ['Invalid JSON body'];
        }

        if (empty($data['fromStationId'])) {
            $errors[] = 'fromStationId is required';
        }
        if (empty($data['toStationId'])) {
            $errors[] = 'toStationId is required';
        }
        if (empty($data['analyticCode'])) {
            $errors[] = 'analyticCode is required';
        }

        return $errors;
    }
}
```

#### Repository Implementation (Doctrine)

```php
// src/Repository/DoctrineRouteRepository.php
namespace App\Repository;

use App\Domain\Entity\Route;
use App\Domain\Repository\RouteRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineRouteRepository extends ServiceEntityRepository implements RouteRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Route::class);
    }

    public function save(Route $route): void
    {
        $this->getEntityManager()->persist($route);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?Route
    {
        return $this->find($id);
    }

    public function getDistancesByAnalyticCode(
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
        string $groupBy = 'none'
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->select('r.analyticCode, SUM(r.distanceKm) as totalDistance');

        if ($groupBy !== 'none') {
            $groupExpr = match($groupBy) {
                'day' => "DATE_FORMAT(r.createdAt, '%Y-%m-%d')",
                'month' => "DATE_FORMAT(r.createdAt, '%Y-%m')",
                'year' => "DATE_FORMAT(r.createdAt, '%Y')",
            };
            $qb->addSelect("$groupExpr as groupKey")
               ->groupBy('r.analyticCode, groupKey')
               ->orderBy('groupKey');
        } else {
            $qb->groupBy('r.analyticCode');
        }

        if ($from) {
            $qb->andWhere('r.createdAt >= :from')
               ->setParameter('from', $from);
        }
        if ($to) {
            $qb->andWhere('r.createdAt <= :to')
               ->setParameter('to', $to->setTime(23, 59, 59));
        }

        return $qb->getQuery()->getResult();
    }
}
```

---

## Graph Builder

```php
// src/Domain/Service/GraphBuilder.php
namespace App\Domain\Service;

class GraphBuilder
{
    public static function fromJsonFile(string $path): array
    {
        $data = json_decode(file_get_contents($path), true);
        $graph = [];

        foreach ($data as $line) {
            foreach ($line['distances'] as $edge) {
                $parent = $edge['parent'];
                $child = $edge['child'];
                $distance = $edge['distance'];

                // Bidirectional
                if (!isset($graph[$parent])) {
                    $graph[$parent] = [];
                }
                if (!isset($graph[$child])) {
                    $graph[$child] = [];
                }

                $graph[$parent][$child] = $distance;
                $graph[$child][$parent] = $distance;
            }
        }

        return $graph;
    }
}
```

---

## JWT Authentication (LexikJWTAuthenticationBundle)

### Configuration

```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600

# config/packages/security.yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            jwt: ~

    access_control:
        - { path: ^/api/v1, roles: IS_AUTHENTICATED_FULLY }
```

### Generate JWT Keys

```bash
# Generate keys
php bin/console lexik:jwt:generate-keypair

# Or manually
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

### .env

```bash
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-passphrase
```

### Custom Authenticator (Optional)

```php
// src/Security/JwtAuthenticator.php
namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;

class JwtAuthenticator extends JWTAuthenticator
{
    // Override methods if custom behavior needed
}
```

---

## CORS Configuration (NelmioCorsBundle)

### config/packages/nelmio_cors.yaml

```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/api/':
            allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
            allow_headers: ['Content-Type', 'Authorization']
            allow_methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
            max_age: 3600
```

### .env

```bash
# Development - allow localhost
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'

# Production - restrict to your domain
# CORS_ALLOW_ORIGIN='^https://yourdomain\.com$'
```

### Why Backend CORS (not nginx)?

1. **Fine-grained control** - per-route configuration
2. **Environment-specific** - different origins for dev/prod
3. **Symfony integration** - works with security firewall
4. **Standard practice** - API frameworks handle CORS

---

## Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# With coverage
./vendor/bin/phpunit --coverage-html coverage/

# Specific test
./vendor/bin/phpunit --filter RouteCalculatorTest

# Watch mode (with phpunit-watcher)
./vendor/bin/phpunit-watcher watch
```

### phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
        <exclude>
            <directory>src/Infrastructure/Config</directory>
        </exclude>
    </source>
</phpunit>
```

---

## XP Practices

### Small Iterations
- Commit after each passing test
- Push frequently
- Keep PRs small and focused

### Refactoring
- After green, always look for improvements
- Extract methods, rename variables
- Remove duplication

### Simple Design
- YAGNI - don't add features "just in case"
- Start with InMemoryRepository, add Postgres when needed
- No premature optimization

### Collective Code Ownership
- Consistent code style (PHPCS enforced)
- Clear naming conventions
- Documentation in code (PHPDoc)

---

## Implementation Order (TDD)

1. **GraphBuilder** - parse distances.json
2. **RouteCalculator** - Dijkstra algorithm
3. **Route entity** - domain model
4. **CalculateRouteHandler** - orchestration
5. **RouteController** - HTTP layer
6. **JwtAuthMiddleware** - security
7. **PostgresRouteRepository** - persistence (bonus)
8. **StatsController** - analytics (bonus)
