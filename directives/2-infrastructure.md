# Infrastructure - Docker, CI/CD & DevOps

## Why Infrastructure First?

1. **Reproducible environment**: Same setup for dev/CI/prod
2. **TDD enablement**: Can't run tests without environment
3. **Fast feedback loop**: `docker compose up` -> code -> test -> repeat
4. **No "works on my machine"**: Everyone uses same containers

---

## Docker Architecture

```
┌─────────────────────────────────────────────────┐
│                   nginx (443/80)                │
│                  reverse proxy                  │
└──────────┬──────────────────┬───────────────────┘
           │                  │
     ┌─────▼─────┐      ┌─────▼─────┐
     │  frontend │      │  backend  │
     │  (Vue/TS) │      │ (PHP-FPM) │
     │   :3000   │      │   :9000   │
     └───────────┘      └─────┬─────┘
                              │
                        ┌─────▼─────┐
                        │    db     │
                        │ postgres  │
                        │   :5432   │
                        └───────────┘
```

---

## docker-compose.yml

Uses **explicit profiles** to separate dev and prod environments:

```yaml
services:
  # Reverse proxy - Production
  nginx:
    container_name: trainrouting-nginx
    profiles: ["prod"]
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./nginx/certs:/etc/nginx/certs:ro
      - frontend_dist:/usr/share/nginx/html/app:ro
    depends_on:
      - backend
      - frontend-builder
    restart: unless-stopped

  # Reverse proxy - Development
  nginx-dev:
    container_name: trainrouting-nginx-dev
    profiles: ["dev"]
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/nginx.dev.conf:/etc/nginx/nginx.conf:ro
      - ./nginx/certs:/etc/nginx/certs:ro
    depends_on:
      - backend
      - frontend
    restart: unless-stopped

  # PHP Backend
  backend:
    container_name: trainrouting-backend
    build:
      context: ./backend
      dockerfile: Dockerfile
    environment:
      - APP_ENV=${APP_ENV:-dev}
      - APP_SECRET=${APP_SECRET}
      - DATABASE_URL=postgresql://${POSTGRES_USER}:${POSTGRES_PASSWORD}@db:5432/${POSTGRES_DB}?serverVersion=16&charset=utf8
      - CORS_ALLOW_ORIGIN=${CORS_ALLOW_ORIGIN}
      - JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
      - JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
      - JWT_PASSPHRASE=${JWT_PASSPHRASE}
    volumes:
      - ./backend:/var/www/html
      - ./data:/data:ro                    # Shared data directory
    depends_on:
      db:
        condition: service_healthy
    healthcheck:
      test: ["CMD-SHELL", "pgrep php-fpm || exit 1"]
      interval: 5s
      timeout: 5s
      retries: 5
      start_period: 10s
    restart: unless-stopped

  # Vue Frontend - Development (hot reload)
  frontend:
    container_name: trainrouting-frontend
    profiles: ["dev"]
    build:
      context: ./frontend
      dockerfile: Dockerfile
      target: dev
    environment:
      - VITE_API_URL=/api/v1
    volumes:
      - ./frontend:/app
      - ./data:/data:ro                    # Shared data directory
    restart: unless-stopped

  # Vue Frontend - Production (build static files)
  frontend-builder:
    container_name: trainrouting-builder
    profiles: ["prod"]
    build:
      context: ./frontend
      dockerfile: Dockerfile
      target: build
    volumes:
      - frontend_dist:/app/dist
      - ./data:/data:ro                    # Shared data directory

  # Database
  db:
    container_name: trainrouting-db
    image: postgres:16-alpine
    environment:
      - POSTGRES_DB=${POSTGRES_DB}
      - POSTGRES_USER=${POSTGRES_USER}
      - POSTGRES_PASSWORD=${POSTGRES_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./docker/postgres:/docker-entrypoint-initdb.d:ro  # Init scripts (creates test DB)
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER} -d ${POSTGRES_DB}"]
      interval: 5s
      timeout: 5s
      retries: 5
    restart: unless-stopped

volumes:
  postgres_data:
  frontend_dist:
```

### Usage

```bash
# Production (compiled static files)
docker compose --profile prod up -d

# Development (hot reload)
docker compose --profile dev up -d

# Stop all containers
docker compose --profile dev --profile prod down --remove-orphans
```

---

## Dockerfiles

### Backend (PHP 8.4-FPM)

```dockerfile
# backend/Dockerfile
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    icu-dev \
    && docker-php-ext-install \
    pdo_pgsql \
    intl \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first (for caching)
COPY composer.json composer.lock* ./

# Install dependencies (if composer files exist)
RUN if [ -f composer.json ]; then composer install --no-dev --no-scripts --no-autoloader; fi

# Copy source code
COPY . .

# Generate autoloader (if vendor exists)
RUN if [ -d vendor ]; then composer dump-autoload --optimize; fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
```

### Frontend (Vue/TS) - Multi-stage

```dockerfile
# frontend/Dockerfile

# Development stage
FROM node:20-alpine AS dev

WORKDIR /app

EXPOSE 3000

# Install dependencies and start dev server
# Dependencies are installed at runtime since source is mounted
CMD ["sh", "-c", "npm install && npm run dev"]

# Build stage
FROM node:20-alpine AS build

WORKDIR /app

COPY package*.json ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

COPY . .
RUN npm run build

# Production stage - copy build to volume
FROM node:20-alpine AS prod

WORKDIR /output

COPY --from=build /app/dist ./dist

# Script to copy files to mounted volume and exit
# This ensures the volume is always refreshed with latest build
CMD ["sh", "-c", "if [ -d /target ]; then rm -rf /target/* && cp -r /output/dist/* /target/; fi; echo 'Build copied to volume'; exit 0"]
```

**Why this approach?**
- The `prod` stage copies built files from `/output/dist` to `/target` (the mounted volume)
- This ensures that on every container start, the volume gets the fresh build
- Solves the Docker volume caching issue where old files persist

---

## Nginx Configuration

Two separate configs for production and development:

### Production Config (nginx/nginx.conf)

Serves static files from the built frontend:

```nginx
# nginx/nginx.conf
events {
    worker_connections 1024;
}

http {
    include mime.types;

    # DNS resolver for Docker
    resolver 127.0.0.11 valid=30s;

    upstream backend {
        server backend:9000;
    }

    # Redirect HTTP to HTTPS
    server {
        listen 80;
        server_name localhost;
        return 301 https://$host$request_uri;
    }

    # HTTPS server
    server {
        listen 443 ssl;
        server_name localhost;

        # SSL certificates
        ssl_certificate /etc/nginx/certs/server.crt;
        ssl_certificate_key /etc/nginx/certs/server.key;

        # SSL settings
        ssl_protocols TLSv1.2 TLSv1.3;
        ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
        ssl_prefer_server_ciphers off;

        # Security headers
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-XSS-Protection "1; mode=block" always;
        add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
        add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:;" always;

        # API routes -> backend (PHP-FPM)
        location /api/ {
            fastcgi_pass backend;
            fastcgi_param SCRIPT_FILENAME /var/www/html/public/index.php;
            include fastcgi_params;
            fastcgi_param REQUEST_URI $request_uri;
            fastcgi_param QUERY_STRING $query_string;
        }

        # Static assets (production)
        location /assets/ {
            alias /usr/share/nginx/html/app/assets/;
            expires 1y;
            add_header Cache-Control "public, immutable";
        }

        # Frontend routes - serve static files (production)
        location / {
            root /usr/share/nginx/html/app;
            try_files $uri $uri/ /index.html;
        }
    }
}
```

### Development Config (nginx/nginx.dev.conf)

Proxies to Vite dev server for hot reload:

```nginx
# nginx/nginx.dev.conf
# Same as production config except for frontend location:

        # Frontend routes - proxy to Vite dev server
        location / {
            set $upstream http://frontend:3000;
            proxy_pass $upstream;
            proxy_http_version 1.1;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection "upgrade";
            proxy_set_header Host $host;
        }
```

### Generate Self-Signed Certificates (Development)

The `make install` and `make install-dev` commands automatically generate SSL certificates if they don't exist. Certificates are **not regenerated** on subsequent runs to avoid browser warnings.

```bash
# Automatic generation (part of make install)
make certs           # Only generates if missing

# Force regeneration (if certificates expire or are corrupted)
make certs-renew     # Deletes and recreates certificates
```

**Manual generation** (if not using Makefile):

```bash
# Create certs directory
mkdir -p nginx/certs

# Generate self-signed certificate
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout nginx/certs/server.key \
  -out nginx/certs/server.crt \
  -subj "/C=CH/ST=Vaud/L=Montreux/O=Dev/CN=localhost"
```

**Important**:
- Certificates are valid for 365 days
- They are self-signed (browser will show warning on first access)
- Once accepted in your browser, they persist until regenerated
- Use `make certs-renew` if certificates expire

### .gitignore for certificates

```gitignore
# Don't commit real certificates
nginx/certs/*.key
nginx/certs/*.crt
!nginx/certs/.gitkeep
```

---

## CI/CD Pipeline (GitHub Actions)

### .github/workflows/ci.yml

```yaml
name: CI/CD Pipeline

on:
  push:
    branches: [main]
    tags: ['v*']
  pull_request:
    branches: [main]

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  # ============ LINT ============
  lint-backend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - run: composer install --working-dir=backend
      - run: ./backend/vendor/bin/phpcs backend/src

  lint-frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json
      - run: cd frontend && npm ci
      - run: cd frontend && npm run lint

  # ============ TEST ============
  test-backend:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_DB: test
          POSTGRES_USER: test
          POSTGRES_PASSWORD: test
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 5432:5432
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          coverage: xdebug
      - run: composer install --working-dir=backend
      - run: |
          cd backend
          ./vendor/bin/phpunit --coverage-clover=coverage.xml
      - name: Check coverage threshold
        run: |
          coverage=$(grep -oP 'lines-valid="\K[\d]+' backend/coverage.xml)
          covered=$(grep -oP 'lines-covered="\K[\d]+' backend/coverage.xml)
          percent=$((covered * 100 / coverage))
          echo "Coverage: $percent%"
          if [ $percent -lt 80 ]; then exit 1; fi

  test-frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json
      - run: cd frontend && npm ci
      - run: cd frontend && npm run test:coverage

  # ============ SECURITY ============
  security-phpstan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - run: composer install --working-dir=backend
      - run: ./backend/vendor/bin/phpstan analyse backend/src --level=8

  security-npm-audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - run: cd frontend && npm audit --audit-level=high
    continue-on-error: true

  security-trivy:
    runs-on: ubuntu-latest
    needs: [build]
    steps:
      - uses: actions/checkout@v4
      - name: Run Trivy scanner
        uses: aquasecurity/trivy-action@master
        with:
          image-ref: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}/backend:${{ github.sha }}
          severity: 'HIGH,CRITICAL'

  # ============ BUILD ============
  build:
    runs-on: ubuntu-latest
    needs: [lint-backend, lint-frontend, test-backend, test-frontend]
    permissions:
      contents: read
      packages: write
    steps:
      - uses: actions/checkout@v4

      - name: Log in to Container registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Build and push backend
        uses: docker/build-push-action@v5
        with:
          context: ./backend
          push: true
          tags: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}/backend:${{ github.sha }}

      - name: Build and push frontend
        uses: docker/build-push-action@v5
        with:
          context: ./frontend
          push: true
          tags: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}/frontend:${{ github.sha }}

  # ============ RELEASE ============
  release:
    runs-on: ubuntu-latest
    needs: [build, security-phpstan, security-trivy]
    if: github.ref == 'refs/heads/main' || startsWith(github.ref, 'refs/tags/')
    permissions:
      contents: read
      packages: write
    steps:
      - uses: actions/checkout@v4

      - name: Log in to Container registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Determine tag
        id: tag
        run: |
          if [[ $GITHUB_REF == refs/tags/* ]]; then
            echo "tag=${GITHUB_REF#refs/tags/}" >> $GITHUB_OUTPUT
          else
            echo "tag=$(date +%Y.%m.%d)-${GITHUB_SHA::7}" >> $GITHUB_OUTPUT
          fi

      - name: Tag and push release images
        run: |
          docker pull ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}/backend:${{ github.sha }}
          docker pull ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}/frontend:${{ github.sha }}
          docker tag ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}/backend:${{ github.sha }} ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}/backend:${{ steps.tag.outputs.tag }}
          docker tag ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}/frontend:${{ github.sha }} ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}/frontend:${{ steps.tag.outputs.tag }}
          docker push ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}/backend:${{ steps.tag.outputs.tag }}
          docker push ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}/frontend:${{ steps.tag.outputs.tag }}
```

---

## Changelog

### Format (Keep a Changelog)

Follow [keepachangelog.com](https://keepachangelog.com) format:

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial project setup with Symfony 7 and Vue.js 3

## [1.0.0] - 2025-01-15

### Added
- Route calculation API endpoint (POST /api/v1/routes)
- Dijkstra algorithm for shortest path
- JWT authentication
- Vue.js frontend with Vuetify 3
- Docker Compose deployment
- CI/CD pipeline with GitHub Actions

### Security
- HTTPS with TLS 1.2/1.3
- Security headers (HSTS, CSP, X-Frame-Options)
- CORS configuration

## [0.1.0] - 2025-01-10

### Added
- Project scaffolding
- Database schema and migrations
- Basic API structure
```

### Change Types

| Type | Description |
|------|-------------|
| `Added` | New features |
| `Changed` | Changes in existing functionality |
| `Deprecated` | Soon-to-be removed features |
| `Removed` | Removed features |
| `Fixed` | Bug fixes |
| `Security` | Vulnerability fixes |

### Versioning Strategy

- **Semantic**: `MAJOR.MINOR.PATCH` (e.g., 1.2.3)
- **Calendar** (alternative): `YYYY.MM.DD` (e.g., 2025.01.15)

For this challenge, semantic versioning is recommended:
- Start at `0.1.0` during development
- Release `1.0.0` when all features complete

### Git Tags

```bash
# Create annotated tag
git tag -a v1.0.0 -m "Release v1.0.0 - Initial release"

# Push tag
git push origin v1.0.0
```

---

## Makefile

A Makefile simplifies common commands and provides a consistent interface.

```makefile
# Makefile

.PHONY: help install start stop restart logs test lint coverage build clean db-migrate db-reset certs

# Colors
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
RESET  := $(shell tput -Txterm sgr0)

## Help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "${GREEN}%-15s${RESET} %s\n", $$1, $$2}'

## Setup
install: certs ## Initial project setup (production)
	@echo "${YELLOW}Cleaning up existing Docker resources...${RESET}"
	docker compose --profile dev --profile prod down --remove-orphans 2>/dev/null || true
	docker network prune -f 2>/dev/null || true
	cp -n .env.example .env || true
	cp -n backend/.env.example backend/.env || true
	@sed -i '' 's/APP_ENV=dev/APP_ENV=prod/' .env 2>/dev/null || sed -i 's/APP_ENV=dev/APP_ENV=prod/' .env
	@sed -i '' 's/APP_ENV=dev/APP_ENV=prod/' backend/.env 2>/dev/null || sed -i 's/APP_ENV=dev/APP_ENV=prod/' backend/.env
	rm -rf backend/vendor
	@echo "${YELLOW}Building images...${RESET}"
	docker compose --profile prod build
	@echo "${YELLOW}Starting containers...${RESET}"
	docker compose --profile prod up -d
	@echo "${YELLOW}Waiting for services to be ready...${RESET}"
	@sleep 5
	@echo "${YELLOW}Installing backend dependencies...${RESET}"
	docker compose exec backend sh -c "composer clear-cache && composer install --no-dev --optimize-autoloader"
	@echo "${YELLOW}Generating JWT keys...${RESET}"
	$(MAKE) jwt-keys
	@echo "${YELLOW}Running database migrations...${RESET}"
	$(MAKE) db-migrate
	@echo "${GREEN}✓ Setup complete! Access https://localhost${RESET}"

install-dev: certs ## Development setup (hot reload)
	@echo "${YELLOW}Cleaning up existing Docker resources...${RESET}"
	docker compose --profile dev --profile prod down --remove-orphans 2>/dev/null || true
	docker network prune -f 2>/dev/null || true
	cp -n .env.example .env || true
	cp -n backend/.env.example backend/.env || true
	@sed -i '' 's/APP_ENV=prod/APP_ENV=dev/' .env 2>/dev/null || sed -i 's/APP_ENV=prod/APP_ENV=dev/' .env
	@sed -i '' 's/APP_ENV=prod/APP_ENV=dev/' backend/.env 2>/dev/null || sed -i 's/APP_ENV=prod/APP_ENV=dev/' backend/.env
	rm -rf backend/vendor
	@echo "${YELLOW}Building images...${RESET}"
	docker compose --profile dev build
	@echo "${YELLOW}Starting containers...${RESET}"
	docker compose --profile dev up -d
	@echo "${YELLOW}Waiting for services to be ready...${RESET}"
	@sleep 5
	@echo "${YELLOW}Installing backend dependencies...${RESET}"
	docker compose exec backend sh -c "composer clear-cache && composer install"
	@echo "${YELLOW}Installing frontend dependencies...${RESET}"
	docker compose exec frontend npm install
	@echo "${YELLOW}Generating JWT keys...${RESET}"
	$(MAKE) jwt-keys
	@echo "${YELLOW}Running database migrations...${RESET}"
	$(MAKE) db-migrate
	@echo "${GREEN}✓ Dev setup complete! Access https://localhost${RESET}"

certs: ## Generate SSL certificates (only if they don't exist)
	@mkdir -p nginx/certs
	@if [ ! -f nginx/certs/server.key ] || [ ! -f nginx/certs/server.crt ]; then \
		echo "${YELLOW}Generating SSL certificates...${RESET}"; \
		openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
			-keyout nginx/certs/server.key \
			-out nginx/certs/server.crt \
			-subj "/C=CH/ST=Vaud/L=Montreux/O=Dev/CN=localhost"; \
		echo "${GREEN}✓ SSL certificates generated${RESET}"; \
	else \
		echo "${GREEN}✓ SSL certificates already exist, skipping generation${RESET}"; \
	fi

certs-renew: ## Force regenerate SSL certificates
	@mkdir -p nginx/certs
	@echo "${YELLOW}Removing old certificates...${RESET}"
	rm -f nginx/certs/server.key nginx/certs/server.crt
	@echo "${YELLOW}Generating new SSL certificates...${RESET}"
	openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
		-keyout nginx/certs/server.key \
		-out nginx/certs/server.crt \
		-subj "/C=CH/ST=Vaud/L=Montreux/O=Dev/CN=localhost"
	@echo "${GREEN}✓ SSL certificates regenerated${RESET}"
	@echo "${YELLOW}Note: You may need to restart nginx (make restart)${RESET}"

## Docker
start: ## Start all containers (prod)
	docker compose --profile dev down --remove-orphans 2>/dev/null || true
	docker compose --profile prod up -d

start-dev: ## Start all containers (dev with hot reload)
	docker compose --profile prod down --remove-orphans 2>/dev/null || true
	docker compose --profile dev up -d

stop: ## Stop all containers
	docker compose --profile dev --profile prod down --remove-orphans

restart: ## Restart all containers (prod)
	docker compose --profile dev --profile prod down --remove-orphans
	docker compose --profile prod up -d

restart-dev: ## Restart all containers (dev)
	docker compose --profile dev --profile prod down --remove-orphans
	docker compose --profile dev up -d

logs: ## Show container logs
	docker compose logs -f

logs-backend: ## Show backend logs
	docker compose logs -f backend

logs-frontend: ## Show frontend logs
	docker compose logs -f frontend

## Testing
test: test-backend test-frontend ## Run all tests

test-backend: ## Run backend tests
	docker compose exec backend ./vendor/bin/phpunit

test-frontend: ## Run frontend tests
	docker compose exec frontend npm test

coverage: coverage-backend coverage-frontend ## Generate coverage reports

coverage-backend: ## Generate backend coverage
	docker compose exec backend ./vendor/bin/phpunit --coverage-html var/coverage
	@echo "${GREEN}Backend coverage: backend/var/coverage/index.html${RESET}"

coverage-frontend: ## Generate frontend coverage
	docker compose exec frontend npm run test:coverage
	@echo "${GREEN}Frontend coverage: frontend/coverage/index.html${RESET}"

## Linting
lint: lint-backend lint-frontend ## Run all linters

lint-backend: ## Lint backend code
	docker compose exec backend ./vendor/bin/phpcs src
	docker compose exec backend ./vendor/bin/phpstan analyse src

lint-frontend: ## Lint frontend code
	docker compose exec frontend npm run lint

lint-fix: ## Fix linting issues
	docker compose exec backend ./vendor/bin/phpcbf src || true
	docker compose exec frontend npm run lint:fix

## Database
db-migrate: ## Run database migrations
	docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction

db-reset: ## Reset database (WARNING: destroys data)
	docker compose down -v
	docker compose up -d db
	sleep 3
	docker compose up -d backend
	$(MAKE) db-migrate

db-shell: ## Open database shell
	docker compose exec db psql -U app -d trainrouting

## Build
build: ## Build production images (no cache)
	docker compose --profile prod --profile dev build --no-cache

rebuild: ## Clean everything and reinstall (production)
	@echo "${YELLOW}Cleaning up all containers, volumes, and networks...${RESET}"
	docker compose --profile dev --profile prod down -v --remove-orphans 2>/dev/null || true
	docker network prune -f 2>/dev/null || true
	rm -rf backend/vendor
	@echo "${YELLOW}Rebuilding from scratch...${RESET}"
	$(MAKE) install

rebuild-dev: ## Clean everything and reinstall (dev mode)
	@echo "${YELLOW}Cleaning up all containers, volumes, and networks...${RESET}"
	docker compose --profile dev --profile prod down -v --remove-orphans 2>/dev/null || true
	docker network prune -f 2>/dev/null || true
	rm -rf backend/vendor
	@echo "${YELLOW}Rebuilding from scratch...${RESET}"
	$(MAKE) install-dev

clean: ## Remove containers, volumes, and generated files
	docker compose --profile dev down -v --remove-orphans
	rm -rf backend/var/cache backend/var/log backend/vendor
	rm -rf frontend/node_modules frontend/dist frontend/coverage
	rm -rf nginx/certs/*.key nginx/certs/*.crt

## Utilities
shell-backend: ## Open backend shell
	docker compose exec backend sh

shell-frontend: ## Open frontend shell
	docker compose exec frontend sh

jwt-keys: ## Generate JWT keys (only if they don't exist)
	@echo "${YELLOW}Checking JWT keys...${RESET}"
	@docker compose exec backend php bin/console lexik:jwt:generate-keypair --skip-if-exists
	@echo "${GREEN}✓ JWT keys ready${RESET}"

jwt-keys-renew: ## Force regenerate JWT keys
	@echo "${YELLOW}Removing old JWT keys...${RESET}"
	docker compose exec backend rm -f config/jwt/private.pem config/jwt/public.pem
	@echo "${YELLOW}Generating new JWT keys...${RESET}"
	docker compose exec backend php bin/console lexik:jwt:generate-keypair
	@echo "${GREEN}✓ JWT keys regenerated${RESET}"
	@echo "${YELLOW}Note: All existing tokens are now invalid${RESET}"
```

### Usage

```bash
# Show all commands
make help

# First time setup (production)
make install

# First time setup (development with hot reload)
make install-dev

# Daily workflow
make start        # prod mode
make start-dev    # dev mode with hot reload
make test
make lint
make stop

# Run specific tests
make test-backend
make coverage

# Database operations
make db-migrate
make db-reset
make db-shell

# Cleanup
make clean
```

---

## Local Development Workflow

### First Setup (with Makefile)
```bash
# Clone and start
git clone <repo>
cd defi-fullstack

# One command setup
make install

# Access
# Frontend: https://localhost
# API: https://localhost/api/v1
```

### Daily Development
```bash
# Start services
make start

# Watch logs
make logs

# Run tests
make test

# Lint code
make lint

# Stop
make stop
```

### Database Access
```bash
# Open psql shell
make db-shell

# Reset database
make db-reset
```

---

## Environment Variables

### .env.example
```bash
# Database
POSTGRES_DB=trainrouting
POSTGRES_USER=app
POSTGRES_PASSWORD=secret

# JWT
JWT_SECRET=your-secret-key-min-32-chars

# App
APP_ENV=dev
```

### Security Notes
- Never commit `.env` file
- Use secrets management in CI (GitHub Actions secrets)
- Rotate JWT_SECRET in production

### JWT Keys Management

JWT keys are automatically generated during `make install` and `make install-dev`. Like SSL certificates, they are **not regenerated** on subsequent runs.

```bash
# Automatic generation (part of make install)
make jwt-keys           # Only generates if missing (--skip-if-exists)

# Force regeneration (if keys are compromised or need rotation)
make jwt-keys-renew     # Deletes and recreates keys
```

**Important**:
- JWT keys are stored in `backend/config/jwt/private.pem` and `public.pem`
- These keys are gitignored for security
- Regenerating keys invalidates all existing JWT tokens
- Use `make jwt-keys-renew` for key rotation in production

**Generating test tokens**:
```bash
make jwt-token          # Generates a token for api_user
```

---

## Deployment Checklist

- [ ] Docker images build successfully
- [ ] All tests pass with coverage thresholds
- [ ] Security scans pass (phpstan, npm audit, trivy)
- [ ] Images pushed to registry
- [ ] `docker compose up -d` works on fresh machine
- [ ] API accessible at /api/v1
- [ ] Frontend loads and connects to API
- [ ] HTTPS configured (production)
