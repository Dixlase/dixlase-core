# Docker Setup
The Docker environment provides a complete local development setup with all services pre-configured.

## Quick Start

```bash
# 1. Clone the repository
git clone <repository-url> dixlase
cd dixlase/docker

# 2. Copy environment file
cp html/.env.example html/.env

# 3. Start all services
docker compose up -d

# 4. Install PHP dependencies
docker exec -i dixlase-laravel.test-1 composer install

# 5. Generate application key
docker exec -i dixlase-laravel.test-1 php artisan key:generate

# 6. Build frontend assets
docker exec -i dixlase-vite-1 npm install
docker exec -i dixlase-vite-1 npm run build

# 7. Open the installer
# Visit http://localhost:8080
```

## Services

| Service | Port | URL | Description |
|---------|------|-----|-------------|
| Web (Nginx) | 8080 | `http://localhost:8080` | Main application |
| Web (HTTPS) | 443 | `https://localhost` | SSL access |
| PHP-FPM | 9000 | (internal) | PHP processing |
| MariaDB | 3306 | (internal) | Database |
| phpMyAdmin | 8081 | `http://localhost:8081` | Database management |
| Vite | 5173 | `http://localhost:5173` | HMR dev server |
| Mailpit | 8025 | `http://localhost:8025` | Email testing |
| Redis | 6379 | (internal) | Cache store |
| Selenium | 4444 | `http://localhost:4444` | Browser testing |

## Default Database Credentials

| Setting | Value |
|---------|-------|
| Host | `mysql` |
| Port | `3306` |
| Database | `dixlase` |
| Username | `dixlase` |
| Password | `dixlase` |
| Root Password | `root` |
| Table Prefix | `dls_` |

Use these values when configuring the [Installation Wizard](../../wizard.md).

## Default Mail Configuration

Mailpit captures all outgoing email for local testing:

| Setting | Value |
|---------|-------|
| Mailer | `smtp` |
| Host | `mailpit` |
| Port | `1025` |
| Encryption | (none) |

View captured emails at `http://localhost:8025`.

## Useful Commands

### Artisan

```bash
# Run any artisan command
docker exec -i dixlase-laravel.test-1 php artisan <command>

# Run migrations
docker exec -i dixlase-laravel.test-1 php artisan migrate

# Clear all caches
docker exec -i dixlase-laravel.test-1 php artisan optimize:clear

# Run tests
docker exec -i dixlase-laravel.test-1 php artisan test --compact
```

### Asset Building

```bash
# Development build with HMR
docker exec -i dixlase-vite-1 npm run dev

# Production build
docker exec -i dixlase-vite-1 npm run build
```

### Container Management

```bash
# Start services
docker compose up -d

# Stop services
docker compose down

# View logs
docker compose logs -f laravel.test

# Rebuild containers (after Dockerfile changes)
docker compose build --no-cache
docker compose up -d
```

## Troubleshooting

### Port Conflicts

If a port is already in use, edit `docker-compose.yml` to change the host port mapping. For example, to change the web port from 8080 to 9090:

```yaml
web:
  ports:
    - "9090:80"   # Changed from 8080
```

### Permission Issues

If you encounter file permission errors inside the container:

```bash
docker exec -i dixlase-laravel.test-1 chmod -R 775 storage bootstrap/cache
docker exec -i dixlase-laravel.test-1 chown -R www-data:www-data storage bootstrap/cache
```

### Database Connection Errors

If the application cannot connect to the database:

1. Confirm the MySQL container is running: `docker compose ps`
2. Check the `.env` file uses `DB_HOST=mysql` (the Docker service name, not `localhost`)
3. Wait a few seconds after starting containers — MariaDB may need time to initialize

### Vite / Asset Errors

If you see "Unable to locate file in Vite manifest":

```bash
docker exec -i dixlase-vite-1 npm run build
```

For hot-reload during development, run `npm run dev` instead.

## Next Steps

Once the containers are running and you can access `http://localhost:8080`, proceed to the [Installation Wizard](../../wizard.md) to complete setup.
