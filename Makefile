.PHONY: help install install-dev start start-dev stop restart restart-dev logs test lint coverage build clean db-migrate db-reset certs

# Colors
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
RESET  := $(shell tput -Txterm sgr0)

## Help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "${GREEN}%-15s${RESET} %s\n", $$1, $$2}'

## Setup
install: certs ## Initial project setup (production)
	@command -v docker >/dev/null 2>&1 || { echo "${YELLOW}Error: docker is not installed. Please install Docker Desktop first.${RESET}"; exit 1; }
	@DOCKER_VERSION=$$(docker version --format '{{.Server.Version}}' 2>/dev/null | cut -d. -f1); \
	if [ -n "$$DOCKER_VERSION" ] && [ "$$DOCKER_VERSION" -lt 25 ]; then \
		echo "${YELLOW}Warning: Docker Engine 25+ is recommended. Current version: $$(docker version --format '{{.Server.Version}}')${RESET}"; \
	fi
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
	@command -v docker >/dev/null 2>&1 || { echo "${YELLOW}Error: docker is not installed. Please install Docker Desktop first.${RESET}"; exit 1; }
	@DOCKER_VERSION=$$(docker version --format '{{.Server.Version}}' 2>/dev/null | cut -d. -f1); \
	if [ -n "$$DOCKER_VERSION" ] && [ "$$DOCKER_VERSION" -lt 25 ]; then \
		echo "${YELLOW}Warning: Docker Engine 25+ is recommended. Current version: $$(docker version --format '{{.Server.Version}}')${RESET}"; \
	fi
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
	@command -v openssl >/dev/null 2>&1 || { echo "${YELLOW}Error: openssl is not installed. Please install it first.${RESET}"; exit 1; }
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
	@echo "${YELLOW}Setting up test database...${RESET}"
	@docker compose exec backend php bin/console doctrine:database:create --if-not-exists --env=test 2>/dev/null || true
	@docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction --env=test --quiet 2>/dev/null || true
	@echo "${YELLOW}Running backend tests...${RESET}"
	docker compose exec backend ./vendor/bin/phpunit

test-frontend: ## Run frontend tests
	docker compose exec frontend npm test

coverage: coverage-backend coverage-frontend ## Generate coverage reports

coverage-backend: ## Generate backend coverage
	@echo "${YELLOW}Setting up test database...${RESET}"
	@docker compose exec backend php bin/console doctrine:database:create --if-not-exists --env=test 2>/dev/null || true
	@docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction --env=test --quiet 2>/dev/null || true
	@echo "${YELLOW}Generating coverage report...${RESET}"
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
	docker compose exec -e APP_ENV=prod backend php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

db-reset: ## Reset database (WARNING: destroys data)
	docker compose down -v
	docker compose up -d db
	sleep 3
	docker compose up -d backend
	$(MAKE) db-migrate

db-shell: ## Open database shell
	docker compose exec db psql -U app -d trainrouting

db-seed: ## Seed database with fake route statistics for testing (usage: make db-seed COUNT=200)
	@chmod +x backend/scripts/seed_routes.sh
	@bash backend/scripts/seed_routes.sh $(COUNT)

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

jwt-keys: ## Generate JWT keys
	docker compose exec backend php bin/console lexik:jwt:generate-keypair --skip-if-exists

jwt-token: ## Generate a JWT token for api_user
	@docker compose exec backend php bin/console lexik:jwt:generate-token api_user
