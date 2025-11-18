# Train Routing Backend

Symfony 7 API backend for the MOB Train Routing application.

## Tech Stack

- PHP 8.4
- Symfony 7
- Doctrine ORM
- PostgreSQL
- PHPUnit
- PHPStan

## Development

```bash
# Install dependencies
composer install

# Run migrations
php bin/console doctrine:migrations:migrate

# Clear cache
php bin/console cache:clear

# Run tests
./vendor/bin/phpunit

# Static analysis
./vendor/bin/phpstan analyse src

# Code style check
./vendor/bin/phpcs src
```

## API Endpoints

- `GET /api/v1/health` - Health check endpoint

## Project Structure

```
src/
├── Controller/     # API controllers
├── Entity/         # Doctrine entities
├── Repository/     # Doctrine repositories
└── Service/        # Business logic services
```

## Configuration

Environment variables are configured in `.env` and can be overridden with `.env.local`.

Key variables:
- `DATABASE_URL` - PostgreSQL connection string
- `APP_ENV` - Environment (dev/prod)
- `APP_SECRET` - Application secret key
