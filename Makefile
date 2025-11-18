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
	cp -n .env.example .env || true
	cp -n backend/.env.example backend/.env || true
	@sed -i '' 's/APP_ENV=dev/APP_ENV=prod/' .env 2>/dev/null || sed -i 's/APP_ENV=dev/APP_ENV=prod/' .env
	@sed -i '' 's/APP_ENV=dev/APP_ENV=prod/' backend/.env 2>/dev/null || sed -i 's/APP_ENV=dev/APP_ENV=prod/' backend/.env
	docker compose --profile dev down --remove-orphans 2>/dev/null || true
	docker compose --profile prod build
	docker compose --profile prod up -d
	docker compose exec backend sh -c "rm -rf vendor && composer install --no-dev --optimize-autoloader"
	$(MAKE) db-migrate
	@echo "${GREEN}Setup complete! Access https://localhost${RESET}"

install-dev: certs ## Development setup (hot reload)
	@command -v docker >/dev/null 2>&1 || { echo "${YELLOW}Error: docker is not installed. Please install Docker Desktop first.${RESET}"; exit 1; }
	cp -n .env.example .env || true
	cp -n backend/.env.example backend/.env || true
	@sed -i '' 's/APP_ENV=prod/APP_ENV=dev/' .env 2>/dev/null || sed -i 's/APP_ENV=prod/APP_ENV=dev/' .env
	@sed -i '' 's/APP_ENV=prod/APP_ENV=dev/' backend/.env 2>/dev/null || sed -i 's/APP_ENV=prod/APP_ENV=dev/' backend/.env
	docker compose --profile prod down --remove-orphans 2>/dev/null || true
	docker compose --profile dev build
	docker compose --profile dev up -d
	docker compose exec backend sh -c "rm -rf vendor && composer install"
	docker compose exec frontend npm install
	$(MAKE) db-migrate
	@echo "${GREEN}Dev setup complete! Access https://localhost${RESET}"

certs: ## Generate SSL certificates
	@command -v openssl >/dev/null 2>&1 || { echo "${YELLOW}Error: openssl is not installed. Please install it first.${RESET}"; exit 1; }
	mkdir -p nginx/certs
	openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
		-keyout nginx/certs/server.key \
		-out nginx/certs/server.crt \
		-subj "/C=CH/ST=Vaud/L=Montreux/O=Dev/CN=localhost"

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
	docker compose exec -e APP_ENV=prod backend php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

db-reset: ## Reset database (WARNING: destroys data)
	docker compose down -v
	docker compose up -d db
	sleep 3
	docker compose up -d backend
	$(MAKE) db-migrate

db-shell: ## Open database shell
	docker compose exec db psql -U app -d trainrouting

## Build
build: ## Build production images
	docker compose build --no-cache

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
