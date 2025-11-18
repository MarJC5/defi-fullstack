# Conventions - Commits, Branches & Code Standards

## Commit Message Format

### Conventional Commits

Follow [conventionalcommits.org](https://www.conventionalcommits.org) format:

```
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

### Types

| Type | Description | Example |
|------|-------------|---------|
| `feat` | New feature | `feat(api): add route calculation endpoint` |
| `fix` | Bug fix | `fix(dijkstra): handle disconnected stations` |
| `docs` | Documentation | `docs: update API usage examples` |
| `style` | Formatting (no code change) | `style: fix indentation in RouteController` |
| `refactor` | Code change (no feature/fix) | `refactor: extract graph builder to service` |
| `test` | Add/update tests | `test: add RouteCalculator edge cases` |
| `chore` | Build, config, dependencies | `chore: update composer dependencies` |
| `perf` | Performance improvement | `perf: cache graph building` |
| `ci` | CI/CD changes | `ci: add coverage threshold check` |
| `build` | Build system changes | `build: optimize Docker image size` |
| `revert` | Revert previous commit | `revert: feat(api): add route endpoint` |

### Scopes

| Scope | Description |
|-------|-------------|
| `api` | Backend API endpoints |
| `ui` | Frontend components |
| `db` | Database/migrations |
| `auth` | Authentication/JWT |
| `docker` | Docker configuration |
| `ci` | CI/CD pipeline |
| `dijkstra` | Routing algorithm |
| `stats` | Statistics feature |

### Examples

```bash
# Feature
feat(api): add POST /routes endpoint

Implements route calculation with Dijkstra algorithm.
Returns path and total distance.

Closes #12

# Bug fix
fix(dijkstra): return correct path for same station

Previously threw error when from === to.
Now returns single-station path with 0 distance.

# Test
test(api): add integration tests for route controller

- Test successful route calculation
- Test unknown station error (422)
- Test validation errors (400)

# Refactor
refactor(backend): extract GraphBuilder to separate service

Improves testability and follows single responsibility principle.

# Chore
chore(deps): update symfony to 7.1

Security patch for CVE-2025-XXXX
```

### Commit Rules

1. **Atomic commits** - One logical change per commit
2. **Present tense** - "add feature" not "added feature"
3. **Imperative mood** - "fix bug" not "fixes bug"
4. **No period** at end of subject line
5. **50/72 rule** - Subject max 50 chars, body wrap at 72
6. **Reference issues** - Use `Closes #123` or `Fixes #123`

---

## Branch Naming

### Format

```
<type>/<short-description>
```

### Examples

```bash
# Features
feature/route-calculation
feature/stats-endpoint
feature/jwt-authentication

# Bug fixes
fix/dijkstra-same-station
fix/cors-headers

# Chores
chore/update-dependencies
chore/docker-optimization

# Documentation
docs/api-examples
docs/deployment-guide
```

### Main Branches

| Branch | Purpose |
|--------|---------|
| `main` | Production-ready code |
| `develop` | Integration branch (optional) |

### Rules

1. Branch from `main`
2. Keep branches short-lived
3. Delete after merge
4. Use lowercase and hyphens

---

## Pull Request Guidelines

### PR Title

Same format as commit messages:

```
feat(api): add route calculation endpoint
```

### PR Description Template

```markdown
## Summary
Brief description of changes (1-3 sentences)

## Changes
- Added RouteController with POST /routes endpoint
- Implemented Dijkstra algorithm in RouteCalculator
- Added unit and integration tests

## Testing
- [ ] Unit tests pass
- [ ] Integration tests pass
- [ ] Manual testing completed
- [ ] Coverage threshold met

## Screenshots (if UI changes)
[Add screenshots here]

## Related Issues
Closes #12
```

### PR Rules

1. **Small PRs** - Max 400 lines changed
2. **One feature** per PR
3. **Tests included** - No PR without tests
4. **Passing CI** - All checks green
5. **Self-review** - Review your own code first

---

## Code Review Checklist

### Functionality
- [ ] Code does what PR description says
- [ ] Edge cases handled
- [ ] Error handling appropriate

### Code Quality
- [ ] Follows DDD structure
- [ ] No code duplication
- [ ] Clear naming
- [ ] No commented-out code

### Testing
- [ ] Unit tests for new code
- [ ] Integration tests if needed
- [ ] Tests are meaningful (not just coverage)

### Security
- [ ] No hardcoded secrets
- [ ] Input validation present
- [ ] SQL injection prevented (using Doctrine)

### Performance
- [ ] No N+1 queries
- [ ] Efficient algorithms
- [ ] No unnecessary loops

---

## Code Style

### PHP (Backend)

Follow PSR-12 (enforced by PHPCS):

```php
// Good
namespace App\Domain\Service;

class RouteCalculator
{
    public function __construct(
        private readonly array $graph
    ) {}

    public function calculate(string $from, string $to): Route
    {
        // Implementation
    }
}

// Bad
namespace App\Domain\Service;
class RouteCalculator {
    private $graph;
    public function __construct($graph) {
        $this->graph = $graph;
    }
}
```

### TypeScript (Frontend)

Follow ESLint + Prettier config:

```typescript
// Good
interface RouteRequest {
  fromStationId: string;
  toStationId: string;
  analyticCode: string;
}

const calculateRoute = async (data: RouteRequest): Promise<Route> => {
  const response = await api.post<Route>('/routes', data);
  return response.data;
};

// Bad
const calculateRoute = async (data: any) => {
  var response = await api.post('/routes', data)
  return response.data
}
```

### Vue Components

```vue
<!-- Good: script setup, typed props -->
<script setup lang="ts">
import { ref } from 'vue';
import type { Station } from '@/types/api';

interface Props {
  stations: Station[];
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
});

const selected = ref<string>('');
</script>

<template>
  <v-select
    v-model="selected"
    :items="stations"
    :loading="loading"
  />
</template>
```

---

## Git Workflow

### Daily Workflow

```bash
# Start new feature
git checkout main
git pull origin main
git checkout -b feature/route-calculation

# Work in small commits
git add src/Domain/Service/RouteCalculator.php
git commit -m "feat(dijkstra): implement shortest path algorithm"

git add tests/Unit/Domain/RouteCalculatorTest.php
git commit -m "test(dijkstra): add unit tests for route calculator"

# Push and create PR
git push -u origin feature/route-calculation
# Create PR on GitHub

# After PR approved and merged
git checkout main
git pull origin main
git branch -d feature/route-calculation
```

### TDD Commit Rhythm

```bash
# RED - Write failing test
git commit -m "test(dijkstra): add test for adjacent stations"

# GREEN - Make it pass
git commit -m "feat(dijkstra): implement basic distance calculation"

# REFACTOR - Improve code
git commit -m "refactor(dijkstra): extract priority queue logic"
```

---

## Documentation Standards

### Code Comments

```php
// Good - Explains WHY
// Using priority queue for O(E log V) complexity instead of O(V^2)
$queue = new \SplPriorityQueue();

// Bad - Explains WHAT (obvious from code)
// Create a new priority queue
$queue = new \SplPriorityQueue();
```

### PHPDoc (when needed)

```php
/**
 * Calculate shortest route between two stations.
 *
 * @throws StationNotFoundException When station ID not in graph
 * @throws NoRouteFoundException When stations are not connected
 */
public function calculate(string $from, string $to, string $analyticCode): Route
```

### TypeScript JSDoc (when needed)

```typescript
/**
 * Calculate route between stations
 * @throws {ApiError} When request fails
 */
async function calculateRoute(data: RouteRequest): Promise<Route>
```

---

## Decisions & Assumptions

Document technical decisions in commit messages or PR descriptions:

### Example Decision Record

```markdown
## Decision: Use Dijkstra over BFS

### Context
Need to find shortest path between stations.

### Options
1. BFS - Simple, O(V+E), unweighted
2. Dijkstra - O(E log V), weighted edges

### Decision
Dijkstra because distances vary between stations.

### Consequences
- Slightly more complex implementation
- Accurate distance calculation
- Can handle weighted graphs
```

### Where to Document

| Decision Type | Location |
|--------------|----------|
| Architecture | PR description |
| Algorithm choice | Code comment + commit |
| Library selection | PR description |
| Trade-offs | DECISIONS.md (optional) |
