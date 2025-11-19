# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- GitHub Actions CI/CD pipeline with lint, test, security, build, and release stages
- CHANGELOG.md documentation

### Changed
- Refactored Route entity to use Value Objects (pure DDD, no Doctrine annotations)
- Extracted File I/O from Application Handler to Infrastructure layer
- Created shared PeriodCalculator utility class
- Added Custom Doctrine Types for Value Object persistence (StationIdType, DistanceType, StationIdArrayType)
- Moved ORM mapping to XML configuration for DDD compliance

### Fixed
- PHPStan type errors in RouteCalculator and PeriodCalculator
- CI workflow database configuration (test_db_test naming)
- CI workflow composer cache directory paths
- CI workflow DISTANCES_PATH environment variable
- Docker init-test-db.sh shebang for Alpine Linux
- PHPCS code style errors in backend controllers and handlers

## [0.5.0] - 2025-11-19

### Added

#### Frontend - Statistics (Bonus)
- StatsChart component with toggle in home view (TDD GREEN)
- useStats composable for statistics data fetching (TDD GREEN)
- Stats service for API communication (TDD GREEN)
- Comprehensive test coverage for stats features

#### Frontend - Authentication
- JWT authentication with httpOnly cookies
- Login form component

#### Frontend - Route Calculator
- RouteForm component for station selection (TDD GREEN)
- RouteResult component for path visualization (TDD GREEN)
- useRoutes composable for route calculation (TDD GREEN)
- Route service for API communication (TDD GREEN)
- API service with interceptors (TDD GREEN)
- API types from OpenAPI specification
- Home view integration with RouteForm and RouteResult

### Fixed
- Allow .mts imports in tsconfig.node.json
- Lint auto-fixes for stats components

## [0.4.0] - 2025-11-18

### Added

#### Backend - Statistics Endpoint (Bonus)
- GET /stats/distances endpoint with date range filtering (TDD GREEN)
- Analytics aggregation by code (day, month, year, none)
- periodStart/periodEnd in stats response (TDD GREEN)

#### Backend - Database Persistence (Bonus)
- Doctrine persistence for Route entity (TDD GREEN)
- Database aggregation for statistics

#### Backend - DDD Architecture
- Value Objects (StationId, Distance) with validation
- Integration of Value Objects into Route entity (TDD GREEN)
- Proper DDD layering with Application layer

#### Documentation
- OpenAPI 3.1 documentation with Swagger UI

### Changed
- Refactored to proper DDD layering with Application layer

## [0.3.0] - 2025-11-17

### Added

#### Backend - Core Features
- RouteController for route calculation (POST /api/v1/routes)
- JWT authentication with Lexik JWT Bundle
- Health checks and proper service dependencies

#### Backend - Domain Services
- RouteCalculator with Dijkstra algorithm (TDD GREEN)
- GraphBuilder service for network construction (TDD GREEN)

### Fixed
- Docker health checks and service dependencies

## [0.2.0] - 2025-11-16

### Added

#### Infrastructure
- Docker Compose orchestration with dev/prod profiles
- Multi-stage Docker builds for backend and frontend
- Nginx reverse proxy with HTTPS/TLS
- Makefile with development workflow commands
- Backend .env.example configuration

### Changed
- Moved JSON data files to data folder and updated mounts

## [0.1.0] - 2025-11-15

### Added
- Initial project scaffolding
- Project directives and documentation structure
- README with challenge requirements and goals
- Vitest configuration with 70% coverage thresholds
