# Symfony Task Manager API

This project is a Symfony-built API designed to manage tasks. It provides endpoints for creating, updating, listing, and deleting tasks, with JWT authentication, making it suitable for task management applications and integrations.

## Tech Stack
- Symfony 7.3
- PHP 8.4
- PostgreSQL
- Docker & Docker Compose
- JWT Authentication
- PHPUnit for testing

## Getting Started

### Prerequisites
1. [Docker](https://www.docker.com/) installed
2. [Docker Compose](https://docs.docker.com/compose/install/) installed

### Step-by-Step Project Setup

#### 1. Clone and Build
```bash
# Clone the repository (if applicable)
# cd /path/to/project

# Build Docker images
docker compose build --pull --no-cache
```

#### 2. Start Services
```bash
# Start containers
docker compose up -d

# Wait for initialization (dependencies will be installed automatically)
# Database migrations are executed automatically
```

#### 3. Configure Environment Variables
```bash
# Create local environment file
cp .env .env.local
```
In the `.env.local` file, set the `APP_SECRET` variable as any 32 character random string.


#### 4. Configure JWT (Essential)
```bash
# Create the JWT config folder
docker compose exec php mkdir -p config/jwt

# Generate the private key
docker compose exec php openssl genrsa -out config/jwt/private.pem 4096

# Generate the public key
docker compose exec php openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```
Update `.env.local` with JWT paths:
```
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
```

#### 5. Configure Database
Set `DATABASE_URL` in `.env.local`:
```bash
DATABASE_URL=postgresql://app:unsafePassword@127.0.0.1:5432/app?serverVersion=16&charset=utf8
```

Verify database connection:
```bash
# Create the databse (if not exists)
docker compose exec php bin/console doctrine:database:create --if-not-exists

# Validate the schema
docker compose exec php bin/console doctrine:schema:validate

# Create test database (for running tests, if not exists)
docker compose exec php bin/console doctrine:database:create --env=test --if-not-exists
```

Run Database Migrations:
```bash
# App database
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# Test database
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction --env=test
```

#### 7. Verify Installation
```bash
# Check if the API is working
curl -k https://localhost/api/status -H "Content-Type: application/json"
```

## Running Tests

```bash
# Run all tests
docker compose exec php bin/phpunit

# Run specific tests
docker compose exec php bin/phpunit tests/Controller/AuthControllerTest.php

# Run tests with coverage
docker compose exec php bin/phpunit --coverage-text
```

## Development

### Important Files

#### Environment Configuration
- **`.env`** - Default environment variables (committed to git)
- **`.env.local`** - Local overrides (not committed, create manually)  
- **`.env.test`** - Test environment config (already configured)
- **`compose.yaml`** - Docker services configuration

#### Automatic Setup
When you run `docker compose up -d`, the following happens automatically:
1. **Dependencies installation** - `composer install` runs automatically
2. **Database migrations** - Applied automatically if migrations exist
3. **Test database creation** - `app_test` database is created

#### Manual Configuration Required
1. **APP_SECRET** - Generate for secure sessions
2. **JWT Keys** - Generate for authentication
3. **Environment variables** - Set in `.env.local`

## Troubleshooting

### Common Issues

#### 1. JWT Error "Key cannot be empty"
```bash
# Solution: Generate JWT keys and configure environment
docker compose exec php mkdir -p config/jwt

docker compose exec php openssl genrsa -out config/jwt/private.pem 4096

docker compose exec php openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

# Add to .env.local
echo "JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem" >> .env.local

echo "JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem" >> .env.local

echo "JWT_PASSPHRASE=" >> .env.local
```

#### 2. Containers don't start
```bash
# Check logs
docker compose logs

# Rebuild
docker compose down --remove-orphans

docker compose build --pull --no-cache

docker compose up -d
```

#### 3. HTTPS certificate error
- Go to `https://localhost` in browser
- Click "Advanced" and "Proceed to localhost (unsafe)"

#### 4. Port already in use
```bash
# Check processes using ports 80/443
lsof -i :80
lsof -i :443

# Stop other services if necessary
sudo systemctl stop apache2  # example
```

#### 5. Database issues
```bash
# Check if database is working
docker compose exec php bin/console dbal:run-sql "SELECT 1"

# Recreate database if necessary
docker compose exec php bin/console doctrine:database:drop --force

docker compose exec php bin/console doctrine:database:create

docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# For test database
docker compose exec php bin/console doctrine:database:create --env=test --if-not-exists
```

#### 6. Environment Variables Not Set
```bash
# If APP_SECRET is missing
echo "APP_SECRET=$(docker compose exec php php -r 'echo bin2hex(random_bytes(32));')" >> .env.local

# If DATABASE_URL is missing
echo "DATABASE_URL=postgresql://app:unsafePassword@127.0.0.1:5432/app?serverVersion=15&charset=utf8" >> .env.local

# Check current environment variables
docker compose exec php php bin/console debug:container --env-vars
```

## System Requirements

- **Docker**: 20.x+
- **Docker Compose**: 2.x+

---

For more information about Symfony, check the [official documentation](https://symfony.com/doc).
