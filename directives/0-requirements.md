# Requirements - Train Routing & Analytics

## Business Context
Railway traffic management system. Trains travel on lines (sometimes interconnected). Each journey has an analytic code (freight, passenger, maintenance, etc.). Goal: calculate distance between stations and provide aggregated statistics.

## Data Files
- `stations.json`: 108 stations with `id`, `shortName`, `longName`
- `distances.json`: 2 rail lines (MOB, MVR-ce) with parent-child station distances in km

### Network Structure
- **MOB line**: MX -> ZW main axis with branches to LENK and IO (Interlaken)
- **MVR-ce line**: VV -> PLEI and BLON -> CABY connections
- Lines connect at CABY station (shared between MOB and MVR-ce)

---

## API Specification (OpenAPI)

### Authentication
- Bearer JWT token required on all endpoints

### Endpoints

#### POST /api/v1/routes
Calculate route A -> B

**Request:**
```json
{
  "fromStationId": "MX",      // required - shortName
  "toStationId": "ZW",        // required - shortName
  "analyticCode": "ANA-123"   // required - for categorization
}
```

**Response 201:**
```json
{
  "id": "uuid",
  "fromStationId": "MX",
  "toStationId": "ZW",
  "analyticCode": "ANA-123",
  "distanceKm": 62.08,
  "path": ["MX", "CGE", "...", "ZW"],
  "createdAt": "2025-01-01T00:00:00Z"
}
```

**Errors:** 400 (invalid request), 422 (unknown station, no route)

#### GET /api/v1/stats/distances (BONUS)
Aggregated distances by analytic code

**Query params:**
- `from` (date, optional): start date
- `to` (date, optional): end date
- `groupBy` (enum: day|month|year|none, default: none)

**Response 200:**
```json
{
  "from": "2025-01-01",
  "to": "2025-12-31",
  "groupBy": "month",
  "items": [
    {
      "analyticCode": "ANA-123",
      "totalDistanceKm": 1245.5,
      "periodStart": "2025-01",
      "periodEnd": "2025-01",
      "group": "2025-01"
    }
  ]
}
```

---

## Technical Stack

### Backend (Required)
- PHP 8.4
- Framework: optional (Symfony, CakePHP, Slim, Laravel)
- Tests: PHPUnit + coverage report
- Linter: PHPCS

### Frontend (Required)
- TypeScript 5
- Framework: Vue.js 3 + Vuetify 3 (recommended)
- Tests: Vitest/Jest + coverage report
- Linter: ESLint, Prettier

### Infrastructure (Required)
- Docker Engine 25
- Docker Compose for orchestration
- Single command deployment: `docker compose up -d`

### Database
- PostgreSQL or MariaDB (persistence optional but recommended for bonus)

---

## CI/CD Pipeline Requirements

1. **Build**: Backend/frontend images
2. **Quality**: lint + tests + coverage (fail if thresholds not met)
3. **Security**: SAST/DAST (phpstan, npm audit, Trivy)
4. **Release**: semantic/calendar tagging, changelog
5. **Delivery**: push to registry, automated deployment

---

## Security Requirements
- HTTPS (TLS 1.2/1.3)
- JWT authentication (Bearer token)
- Secure headers:
  - `X-Frame-Options: SAMEORIGIN`
  - `X-Content-Type-Options: nosniff`
  - `X-XSS-Protection: 1; mode=block`
  - `Strict-Transport-Security` (HSTS)
  - `Content-Security-Policy`
- Secrets management (no .env in commits)
- CORS configuration

---

## Deliverables
- Deployable project (docker-compose, registry image, or zip)
- Clear deployment instructions
- Git history with atomic commits

---

## Core Features

### F1: Route Calculation
- Input: fromStation, toStation, analyticCode
- Output: calculated path + total distance
- Algorithm: pathfinding through connected stations (Dijkstra for bonus)
- Persistence: optional but enables bonus features

### F2: Statistics (BONUS)
- Aggregate distances by analyticCode
- Filter by date range
- Group by time period

### F3: Frontend UI
- Form to create route (select stations + enter code)
- Display result (path + distance)
- Statistics visualization (BONUS)

---

## Evaluation Criteria
1. OpenAPI strict conformity
2. Test coverage with thresholds
3. Docker deployment in 1-2 commands
4. Clean TypeScript frontend with tests
5. Functional CI/CD pipeline
6. Security implementation
7. Code quality & atomic commits
