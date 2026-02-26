# SafetyShield Laravel API

A robust API-centric application built with Apiato framework, featuring OAuth2 authentication, role-based access control, and comprehensive testing capabilities.

## Table of Contents

- [Requirements](#requirements)
- [Setup](#setup)
  - [Docker Setup](#docker-setup)
  - [Local Development Setup](#local-development-setup)
  - [Environment Configuration](#environment-configuration)
- [Authentication & Authorization](#authentication--authorization)
- [Testing](#testing)
- [Code Quality & Linting](#code-quality--linting)
- [API Usage](#api-usage)
- [Development Commands](#development-commands)

## Requirements

- **PHP**: 8.2 or higher
- **Composer**: Latest version
- **Database**: SQLite (default) or MySQL (optional)
- **Redis**: (optional, for caching and sessions)
- **Docker**: (optional, for containerized development)

## Setup

### Docker Setup

The recommended way to run the application is using Docker. Run this command from the **project root**:

```bash
# From project root (/path/to/scheduling-project)
docker-compose up -d
```

This will start a complete development environment with the following services. **All initialization tasks are automated** - migrations, seeding, Passport setup, and configuration caching happen automatically on first startup:

- **Laravel Application** (PHP-FPM) on container `laravel_app`
- **Nginx Web Server** on `http://localhost:8080`
- **MySQL Database** on `localhost:3306`
- **Redis Cache/Sessions** on `localhost:6379`
- **MinIO (S3-compatible storage)** on `localhost:9002` (Console: `localhost:9001`)

**Docker Services:**
- `app`: PHP 8.4-FPM with Laravel application
- `nginx`: Nginx web server serving the application
- `mysql-image`: MySQL 8.0 database server
- `redis-image`: Redis Alpine for caching and sessions
- `minio-image`: MinIO S3-compatible object storage

**Service Details:**
- **MySQL**: Database `laravel`, user `laravel`, password `laravel`
  - Includes healthcheck to ensure readiness before app starts
- **Redis**: No authentication, available on default port
- **MinIO**: Access Key `minio`, Secret Key `minio123`
  - S3 API: `http://localhost:9002`
  - Web Console: `http://localhost:9001`
  - Bucket setup is automated via `minio-setup` service

**Docker Entrypoint:**
The Laravel container uses an automated entrypoint script (`docker-entrypoint.sh`) that handles all initialization tasks. The script:
- Waits for MySQL to be healthy before proceeding
- Runs migrations, seeds, and permission syncs automatically
- Creates Passport keys and password client if they don't exist
- Extracts and saves Passport credentials to `.env` automatically

To stop the containers:
```bash
docker-compose down
```

To completely remove containers and volumes:
```bash
docker-compose down -v
```

**Accessing Services:**
- **Application**: `http://apiato.test` (or `http://localhost:8080`)
- **API**: `http://box.localhost` (or `http://localhost:8080`)
- **MinIO Console**: `http://localhost:9001` (login: `minio` / `minio123`)
- **MySQL**: Connect via any MySQL client to `localhost:3306`
- **Redis**: Connect via Redis client to `localhost:6379`

**Automated Initialization:**

When the Docker container starts, the application automatically:
- ✅ Waits for MySQL to be ready (with healthcheck)
- ✅ Runs database migrations
- ✅ Seeds initial deployment data
- ✅ Syncs application permissions
- ✅ Generates Passport encryption keys (if missing)
- ✅ Creates Passport password client (if missing) and saves credentials to `.env`
- ✅ Generates Swagger API documentation
- ✅ Caches configuration and routes

The Passport client credentials (`CLIENT_WEB_ID` and `CLIENT_WEB_SECRET`) are automatically extracted and added to your `.env` file. You don't need to run these commands manually after the initial setup.

**Troubleshooting Commands:**

If you need to manually run setup commands (e.g., after pulling new code, database issues, or troubleshooting), use these commands within the Docker container:

All commands should be executed within the docker container using `docker exec [container_name]`, for example:
```bash
docker exec laravel_app php artisan migrate
```

- **Database migrations**
  Updates the DB to the latest version by creating new tables or adding/removing DB columns. Use this if migrations weren't applied automatically or after pulling new migration files.
  ```bash
  docker exec laravel_app php artisan migrate
  ```

- **DB Seed**
  Seeds the database with required deployment data. Safe to use after each pull if you need to refresh initial data.
  ```bash
  docker exec laravel_app php artisan apiato:seed-deploy
  ```

- **Testing Seed**
  Fills the DB with testing data. Must be used after seed-deploy if you need test data.
  ```bash
  docker exec laravel_app php artisan apiato:seed-test
  ```

- **Create Passport Client**
  Creates a new Passport password client. Only needed if the automatic creation failed or if you need a new client. The credentials will be displayed - copy them to `.env` as `CLIENT_WEB_ID` and `CLIENT_WEB_SECRET`.
  ```bash
  docker exec laravel_app php artisan passport:client --password
  ```

- **Sync application permissions**
  Updates different roles' permissions based on `permissions.json` files found in each container. Use this if permissions seem incorrect or after pulling new permission configurations.
  ```bash
  docker exec laravel_app php artisan permissions:sync
  ```

- **Cache application routes**
  Updates routes cache to include newly added routes. Use this if routes aren't working correctly or after adding new routes.
  ```bash
  docker exec laravel_app php artisan route:cache
  ```

- **Generate Swagger documentation**
  Generates the Swagger API documentation. Use this if the API docs are outdated or after adding new API endpoints.
  ```bash
  docker exec laravel_app php artisan l5-swagger:generate
  ```

- **Clear all caches**
  Clears application, config, route, and view caches. Useful if you're experiencing caching issues.
  ```bash
  docker exec laravel_app php artisan cache:clear
  docker exec laravel_app php artisan config:clear
  docker exec laravel_app php artisan route:clear
  ```


**MinIO Setup:**
The MinIO bucket (`docker-bucket`) is created automatically by the `minio-setup` service. If you need to manually create or manage buckets:
1. Visit MinIO Console at `http://localhost:9001`
2. Login with credentials: `minio` / `minio123`
3. Create or manage buckets as needed

### Local Development Setup

For local development without Docker:

1. **Clone and Navigate:**
   ```bash
   cd services/laravel
   ```

2. **Install Dependencies:**
   ```bash
   composer install
   ```

3. **Environment Configuration:**
   ```bash
   cp .env.example .env
   ```

4. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

5. **Database Setup:**
   
   **Option A: SQLite (Default - Recommended for Local Development)**
   ```bash
   # Create SQLite database
   touch database/database.sqlite
   
   # Ensure .env is configured for SQLite
   DB_CONNECTION=sqlite
   # Comment out other DB_* variables
   
   # Run migrations
   php artisan migrate
   
   # Seed initial data
   php artisan apiato:seed-deploy
   ```
   
   **Option B: Local MySQL Server**
   ```bash
   # First, ensure you have MySQL installed and running locally
   # Create a database (via MySQL client or phpMyAdmin)
   
   # Update .env file with your local MySQL credentials:
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_mysql_username
   DB_PASSWORD=your_mysql_password
   
   # Run migrations
   php artisan migrate
   
   # Seed initial data
   php artisan apiato:seed-deploy
   ```

6. **Install Passport (OAuth2):**
   ```bash
   php artisan passport:install
   ```

7. **Storage Link:**
   ```bash
   php artisan storage:link
   ```

8. **Cache Configuration:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   ```

9. **Start Development Server:**
   ```bash
   php artisan serve
   ```

The application will be available at `http://localhost:8000`.

### Environment Configuration

The application supports multiple database configurations through the `.env` file:

#### SQLite (Default - Recommended for Local Development)
```env
DB_CONNECTION=sqlite
# Comment out other DB_* variables
```

#### MySQL (Production/Docker)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1          # localhost for local, mysql-image for docker
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel        # Use 'laravel' for Docker, 'root' for local
DB_PASSWORD=laravel        # Use 'laravel' for Docker
```

#### Redis Configuration
```env
# Docker/Production
REDIS_HOST=127.0.0.1       # localhost for local, redis-image for docker
REDIS_PORT=6379
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# For database fallback (local dev without Redis)
# CACHE_DRIVER=database
# QUEUE_CONNECTION=database
# SESSION_DRIVER=database
```

#### MinIO/S3 Configuration

Use 'DEFAULT_DIRECTORY' env variable to set the default directory the app should use under the disk driver (s3/local)

```env
# Docker MinIO Setup
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=minio
AWS_SECRET_ACCESS_KEY=minio123
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=docker-bucket
AWS_ENDPOINT=http://localhost:9002
DEFAULT_DIRECTORY='ss_files'

# Local Development (file storage)
# FILESYSTEM_DISK=local

# Production S3
# FILESYSTEM_DISK=s3
# AWS_ACCESS_KEY_ID=your_access_key
# AWS_SECRET_ACCESS_KEY=your_secret_key
# AWS_DEFAULT_REGION=us-west-2
# AWS_BUCKET=your-production-bucket
# DEFAULT_DIRECTORY='ss_files'
# AWS_ENDPOINT=null  # Use default S3 endpoint
```

**⚠️ Important Note:**
Make sure to set the `FRONTEND_URL` variable in your `.env` file to match your frontend application URL:
```env
FRONTEND_URL=http://localhost:3000  # For local development
# FRONTEND_URL=https://yourdomain.com  # For production
```

## Authentication & Authorization

This application uses **Laravel Passport** for OAuth2 authentication with role-based access control.

### Authentication Flow

1. **User Registration/Login**
   - Web-based login via session guards
   - API authentication via OAuth2 tokens

2. **Token-Based API Access**
   - Access tokens for API requests
   - Refresh tokens for token renewal
   - Configurable token expiration

3. **Role-Based Authorization**
   - Super Admin, Admin, and User roles
   - Policy-based permissions
   - Route-level middleware protection

### Configuration

**Authentication Settings:**
```env
AUTHENTICATION_ENABLED=true    # Enable/disable auth middleware
AUTHORIZATION_ENABLED=true     # Enable/disable policy checks
AUTH_GUARD=api                 # Default guard for API routes

# Token Expiration (minutes)
API_TOKEN_EXPIRES=1440         # 24 hours
API_REFRESH_TOKEN_EXPIRES=43200 # 30 days

# Password Security
BCRYPT_ROUNDS=12               # Password hashing strength
```

**OAuth2 Setup:**

**For Docker environments:** Passport setup is automated. The password client is created automatically on container startup, and credentials are saved to `.env`.

**For local development (without Docker):**
```bash
# Install Passport keys
php artisan passport:keys

# Create password grant client
php artisan passport:client --password
```
Copy the client ID & Key to the `.env` variables (`CLIENT_WEB_ID` & `CLIENT_WEB_SECRET`)

### API Authentication Example

```bash
# Login and get access token
curl -X POST http://localhost:8000/v1/clients/web/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password"
  }'

# Use token for API requests
curl -X GET http://localhost:8000/v1/users \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

## Testing

The application includes comprehensive test suites with **PHPUnit**.

### Test Structure

```
app/Containers/{SectionName}/{ContainerName}/Tests/
├── Unit/              # Container-specific unit tests
├── Functional/        # Container API tests
└── UnitTestCase.php   # Container test base
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test {path/to/container/test}

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/

```

### Test Configuration

Tests use SQLite in-memory database by default:
```xml
<!-- phpunit.xml -->
<env name="APP_ENV" value="testing"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

## Code Quality & Linting

The project uses multiple tools for code quality assurance:

### Linting Tools

1. **PHP CS Fixer** - Code style formatting
2. **PHPStan** - Static analysis
3. **Psalm** - Type checking and analysis
4. **PHPMD** - Mess detection

### Configuration Files

- `.php-cs-fixer.dist.php` - PHP CS Fixer rules
- `phpstan.neon.dist` - PHPStan configuration
- `psalm.xml` - Psalm settings
- `ruleset.xml` - PHPMD rules

### Running Linting

```bash
# Fix code style issues
composer run lint
# OR
vendor/bin/php-cs-fixer fix

# Static analysis
vendor/bin/phpstan analyse

```

### Pre-commit Setup

For consistent code quality, set up pre-commit hooks:
```bash
# Add to .git/hooks/pre-commit
#!/bin/sh
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpstan analyse --no-progress
vendor/bin/psalm --no-progress
```

## API Usage

### Base URLs
- **Local Development**: `http://localhost:8000/v1`
- **Docker**: `http://box.localhost/v1` (or `http://localhost:8080/v1`)
- **Application Frontend**: `http://localhost:3000` (Docker) 

### Common Endpoints

```bash
# Authentication
POST /v1/auth/login
POST /v1/auth/register
POST /v1/auth/refresh
POST /v1/auth/logout

# User Management
GET    /v1/users           # List users (paginated)
POST   /v1/users           # Create user
GET    /v1/users/{id}      # Get user details
PUT    /v1/users/{id}      # Update user
DELETE /v1/users/{id}      # Delete user
GET    /v1/profile         # Get current user profile

# Documentation
GET /api/documentation     # Swagger API docs
```

### Response Format

All API responses follow a consistent format:
```json
{
  "data": {},
  "meta": {
    "pagination": {
      "total": 100,
      "per_page": 10,
      "current_page": 1
    }
  }
}
```

### Rate Limiting

API requests are rate-limited:
```env
GLOBAL_API_RATE_LIMITER_ENABLED=true
GLOBAL_API_RATE_LIMITER_ATTEMPTS_PER_MIN=30
```

## Development Commands

### Essential Artisan Commands

```bash
# Application
php artisan key:generate        # Generate app key
php artisan serve              # Start dev server
php artisan storage:link       # Link storage directory

# Database
php artisan migrate            # Run migrations
php artisan migrate:fresh      # Fresh migration
php artisan apiato:seed-deploy          # Seed database for initial deployment (only use after fresh install)
php artisan apiato:seed-test          # Seed database to add testing data (each time used will generate new data to the db)
php artisan migrate:refresh --seed # Refresh with seeding

# Caching
php artisan config:cache       # Cache configuration
php artisan route:cache        # Cache routes
php artisan cache:clear        # Clear application cache

# OAuth2 / Passport
php artisan passport:install   # Install Passport
php artisan passport:client    # Create OAuth client

# Queue & Jobs
php artisan queue:work         # Process queue jobs
php artisan queue:restart      # Restart queue workers

# API Documentation
php artisan l5-swagger:generate # Generate API docs
```

### Code Generation

#### Creating New Containers

Use the custom SafetyShield container generator to quickly scaffold a complete container structure:

```bash
php artisan safetyshield:create:container
```

This command will prompt you for:
- **Section name** (e.g., AppSection)
- **Container name** (e.g., Product, Order, Invoice)

**What gets generated:**
- Complete folder structure with `.gitkeep` files
- Model, Repository, Policy, Service Provider
- Gate Enum with CRUD permissions
- Controller with CRUD methods
- 5 Request classes (List, Show, Create, Update, Delete)
- Transformer, Routes, Config, DTO, Factory, Migration
- Test cases (Unit & Functional)
- README and permissions.json templates

#### Adding Actions and Tasks

After creating a container, add business logic using SafetyShield generators:

```bash
# Add an Action (orchestrates business logic)
php artisan safetyshield:add:action CreateProduct --container=Product

# Add a Task (single-responsibility operation)
php artisan safetyshield:add:task FindProductById --container=Product
```

**Options:**
- Pass name as argument or it will prompt
- Use `--section` and `--container` flags to skip prompts

**Example:**
```bash
php artisan safetyshield:add:action CreateOrderAction --section=AppSection --container=Order
php artisan safetyshield:add:task CalculateTotalTask --section=AppSection --container=Order
```

#### Available SafetyShield Commands

```bash
# Container & Structure
safetyshield:create:container    # Create complete container structure

# Business Logic
safetyshield:add:action          # Add Action to container
safetyshield:add:task            # Add Task to container
safetyshield:add:model           # Add Model to container

# API Layer
safetyshield:add:request         # Add Request validation class
safetyshield:add:route           # Add Route file

# Database
safetyshield:add:migration       # Add Migration file

# Testing
safetyshield:add:unittest        # Add Unit Test
safetyshield:add:functionaltest  # Add Functional/API Test
```

All commands support:
- Interactive prompts (just run the command)
- Arguments and options (e.g., `--container=Product --section=AppSection`)

#### Using Apiato Generators

For other components, use built-in Apiato generators:

```bash
# List all available Apiato generators
php artisan list apiato:make

# Common generators
php artisan apiato:make:request StoreProductRequest --container=Product
php artisan apiato:make:test:unit CreateProductActionTest --container=Product
```

---

## Security

This application implements several security best practices:

- **Policy checks** on all data access operations
- **Try/catch blocks** around critical operations
- **Comprehensive logging** via Applog function
- **OAuth2 authentication** with token expiration
- **Role-based authorization** with middleware protection
- **Input validation** and sanitization
- **CORS protection** and security headers

For security issues, please report them through appropriate channels.

---
