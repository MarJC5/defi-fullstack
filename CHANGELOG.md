# Release Highlights

> Full release notes with detailed commit history are available in [GitHub Releases](https://github.com/MarJC5/defi-fullstack/releases).

## 🎯 Key Features

### Domain-Driven Design (DDD)
- Pure Domain entities with Value Objects (StationId, Distance)
- XML ORM mapping to keep Domain layer clean
- Custom Doctrine Types for Value Object persistence
- Clear separation: Domain → Application → Infrastructure

### Full-Stack Route Calculator
- **Backend**: Dijkstra algorithm, RESTful API, JWT authentication
- **Frontend**: Vue 3 + TypeScript, Vitest TDD, composables architecture
- **Bonus**: Statistics endpoint with aggregation (day/month/year)

### Production-Ready Infrastructure
- Docker Compose with dev/prod profiles
- CI/CD pipeline: lint → test → security scan → build → release
- Automated GitHub Releases with calendar tagging
- PostgreSQL persistence with Doctrine ORM

### Test-Driven Development
- Backend: PHPUnit with 70%+ coverage
- Frontend: Vitest with 70%+ coverage
- Integration tests for all endpoints
- E2E authentication flow

---

**Detailed commit history and changes below** ⬇️
