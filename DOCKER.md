# DOCKER SETUP GUIDE

## Quick Start with Docker (Recommended)

### Prerequisites
- Docker installed
- Docker Compose installed

### Complete Stack Installation (1 Minute) ⚡

#### Start Everything with One Command
```bash
# Build and start both database and application
docker compose up -d

# Wait for services to be ready (~10 seconds)
docker compose ps

# Check logs if needed
docker compose logs -f app
```

#### Access the Application
```
http://localhost:8000
```

That's it! Your complete search engine is now running in Docker.

---

## What's Running?

### Services Included

#### 1. PostgreSQL 17 (Database)
- **Container:** `search_engine_db`
- **Port:** `5432`
- **Status:** Auto-starts with health checks
- **Data:** Persisted in Docker volume

#### 2. PHP Application (Search Engine)
- **Container:** `search_engine_app`
- **Port:** `8000`
- **Technology:** PHP 8.2 with built-in server
- **Features:** 
  - Live code reloading (changes reflect immediately)
  - Automatic database connection
  - Health monitoring

#### 3. pgAdmin 4 (Optional Database UI)
- **Container:** `search_engine_pgadmin`
- **Port:** `8080`
- **Start with:** `docker compose --profile tools up -d`

---

## Docker Commands

### Basic Operations

```bash
# Start everything
docker compose up -d

# View status
docker compose ps

# View logs (all services)
docker compose logs -f

# View app logs only
docker compose logs -f app

# View database logs only
docker compose logs -f postgres

# Restart a service
docker compose restart app

# Stop everything
docker compose down

# Stop and remove all data (fresh start)
docker compose down -v
```

### Development Workflow

```bash
# Make code changes in your editor
# Changes are automatically reflected (no restart needed)

# Restart app if needed
docker compose restart app

# Rebuild after Dockerfile changes
docker compose up -d --build app

# View real-time logs
docker compose logs -f app
```

---

## Initial Data Import

### Option 1: Import Sample Data
```bash
# Access the app container
docker compose exec app php scripts/seed-database.php
# Type 'yes' when prompted
```

### Option 2: Run from Host (if PHP installed locally)
```bash
php scripts/seed-database.php
# Uses DB_HOST=postgres from environment
```

---

## Configuration

### Environment Variables

The application uses environment variables defined in `docker-compose.yml`. 

**Database Connection (configured automatically):**
```yaml
DB_HOST: postgres        # Container name (internal network)
DB_PORT: 5432
DB_NAME: search_engine
DB_USER: search_user
DB_PASSWORD: search_password_2026
```

**Application Settings:**
```yaml
APP_ENV: development
APP_DEBUG: "true"
RESULTS_PER_PAGE: 10
DEFAULT_SEARCH_MODE: and
```

### Using .env File (Optional)

Create a `.env` file from the example:
```bash
cp .env.example .env
```

Note: `docker-compose.yml` environment variables take precedence over `.env` file.

---

## Architecture

### Container Networking

```
┌─────────────────────────────────────┐
│  Host Machine (Your Computer)       │
│                                     │
│  Browser → http://localhost:8000   │
└────────────┬────────────────────────┘
             │
             ↓
┌─────────────────────────────────────┐
│  Docker Network                     │
│  (search_engine_network)            │
│                                     │
│  ┌─────────────┐  ┌──────────────┐ │
│  │   app       │→ │  postgres    │ │
│  │  (PHP 8.2)  │  │  (PG 17)     │ │
│  │  :8000      │  │  :5432       │ │
│  └─────────────┘  └──────────────┘ │
└─────────────────────────────────────┘
```

### Data Persistence

- **Source Code**: Mounted as volume (changes reflected immediately)
- **Database Data**: Docker volume `postgres_data` (persists across restarts)
- **Logs**: Mounted directory `./logs` (accessible from host)

---

## Database Access

### Connect to PostgreSQL CLI
```bash
# From host
docker compose exec postgres psql -U search_user -d search_engine

# Run SQL commands
\dt                    # List tables
\d documents          # Describe table
SELECT COUNT(*) FROM documents;
```

### Run SQL Files
```bash
docker compose exec -T postgres psql -U search_user -d search_engine < scripts/setup-database.sql
```

### Backup Database
```bash
docker compose exec -T postgres pg_dump -U search_user search_engine > backup.sql
```

### Restore Database
```bash
docker compose exec -T postgres psql -U search_user -d search_engine < backup.sql
```

---

## pgAdmin Setup (Optional)

### 1. Start pgAdmin
```bash
docker compose --profile tools up -d
```

### 2. Access pgAdmin
Open http://localhost:8080

**Login:**
- Email: `admin@searchengine.local`
- Password: `admin123`

### 3. Add Server Connection
1. Right-click "Servers" → "Register" → "Server"
2. **General Tab:** Name: `Search Engine DB`
3. **Connection Tab:**
   - Host: `postgres`
   - Port: `5432`
   - Database: `search_engine`
   - Username: `search_user`
   - Password: `search_password_2026`
4. Click "Save"

---

## Volume Management

### Backup Volume Data
```bash
mkdir -p backups
docker compose exec -T postgres pg_dump -U search_user search_engine | gzip > backups/backup-$(date +%Y%m%d-%H%M%S).sql.gz
```

### Restore from Backup
```bash
gunzip -c backups/backup-20260123-120000.sql.gz | \
  docker compose exec -T postgres psql -U search_user -d search_engine
```

### Clean Up Old Data
```bash
# Remove all volumes and start fresh
docker compose down -v
docker compose up -d
```

---

## Troubleshooting

### Issue: App container won't start
```bash
# Check logs
docker compose logs app

# Check if database is healthy
docker compose ps

# Rebuild the container
docker compose up -d --build app
```

### Issue: "Connection refused" to database
```bash
# Verify postgres is healthy
docker compose ps postgres

# Check network connectivity
docker compose exec app ping postgres

# Restart services in order
docker compose restart postgres
docker compose restart app
```

### Issue: Code changes not reflecting
```bash
# Check volume mount
docker compose exec app ls -la /var/www/html

# Verify volume in docker-compose.yml
docker compose config

# Restart app container
docker compose restart app
```

### Issue: Port already in use
```bash
# Check what's using port 8000
sudo netstat -tulpn | grep 8000

# Kill the process or change port in docker-compose.yml
# Then restart
docker compose up -d
```

### Issue: Permission denied errors
```bash
# Create logs directory if it doesn't exist
mkdir -p logs
chmod 777 logs

# Restart app
docker compose restart app
```

### Issue: Database schema not created
```bash
# Run setup manually
docker compose exec -T postgres psql -U search_user -d search_engine < scripts/setup-database.sql

# Verify tables exist
docker compose exec postgres psql -U search_user -d search_engine -c "\dt"
```

---

## Complete Command Reference

### Security Improvements

1. **Use environment variables from external source:**
```bash
# Don't commit sensitive data to docker-compose.yml
# Use .env file or external secrets manager
```

2. **Remove unnecessary port mappings:**
```yaml
postgres:
  # Remove if app runs in same network
  # ports:
  #   - "5432:5432"
```

3. **Use PHP-FPM with Nginx for production:**
```yaml
# See docker-compose.prod.yml example
# Better performance and security than built-in server
```

4. **Enable production optimizations:**
```yaml
environment:
  APP_ENV: production
  APP_DEBUG: "false"
  ENABLE_QUERY_CACHE: "true"
```

### Health Checks & Monitoring

Both services include health checks:
- **PostgreSQL**: `pg_isready` command
- **Application**: HTTP response check on port 8000

Monitor with:
```bash
docker compose ps          # Service health status
docker stats              # Resource usage
docker compose logs -f    # Application logs
```

---

## Advanced Configuration

### Custom PHP Configuration

Create `php.ini` and mount it:

```yaml
app:
  volumes:
    - ./php.ini:/usr/local/etc/php/php.ini:ro
```

### Resource Limits

Add to services in `docker-compose.yml`:

```yaml
app:
  deploy:
    resources:
      limits:
        cpus: '1'
        memory: 512M
      reservations:
        cpus: '0.5'
        memory: 256M
```

### Multiple Environments

Create separate compose files:
- `docker-compose.yml` - Development
- `docker-compose.prod.yml` - Production

Use with:
```bash
docker compose -f docker-compose.prod.yml up -d
```

---

## FAQ

### Q: Do I need PHP installed on my machine?
**A:** No! Everything runs in Docker containers. You only need Docker and Docker Compose.

### Q: How do I update my code?
**A:** Just edit files normally. Changes are automatically reflected because the source code is mounted as a volume.

### Q: Can I use this for production?
**A:** The current setup uses PHP's built-in server, which is for development only. For production, use PHP-FPM with Nginx (see Production Deployment section).

### Q: How do I reset everything?
**A:** Run `docker compose down -v` to stop and remove all containers and data, then `docker compose up -d` to start fresh.

### Q: Why is DB_HOST=postgres instead of localhost?
**A:** Inside Docker, containers communicate using container names on an internal network. The app container sees the database as `postgres`, not `localhost`.

### Q: How do I see what's happening?
**A:** Use `docker compose logs -f app` to see real-time application logs.

### Q: Can I run just the database in Docker?
**A:** Yes! Set `DB_HOST=localhost` in your `.env`, run `docker compose up -d postgres`, and use `php -S localhost:8000` locally.

---

## Migration Guide

### From Old Setup (Database Only) to Full Docker Stack

1. **Stop local PHP server** (if running)
2. **Update or create .env:**
   ```bash
   cp .env.example .env
   # DB_HOST should be 'postgres'
   ```
3. **Start everything:**
   ```bash
   docker compose down        # Stop old containers
   docker compose up -d       # Start new stack
   ```
4. **Import data if needed:**
   ```bash
   docker compose exec app php scripts/seed-database.php
   ```

Your database data is preserved in the Docker volume!

---

## Production Deployment

```bash
# === Quick Start ===
docker compose up -d                   # Start everything
docker compose ps                      # Check status
docker compose logs -f app             # View app logs
open http://localhost:8000             # Open in browser

# === Management ===
docker compose up -d                   # Start all services
docker compose down                    # Stop all services
docker compose down -v                 # Stop + delete all data
docker compose restart app             # Restart app only
docker compose restart postgres        # Restart database only
docker compose up -d --build app       # Rebuild and restart app

# === Logs & Monitoring ===
docker compose logs -f                 # All logs (follow)
docker compose logs -f app             # App logs only
docker compose logs -f postgres        # Database logs only
docker compose logs --tail=100 app     # Last 100 lines
docker stats search_engine_app         # Resource usage

# === Database Operations ===
docker compose exec postgres psql -U search_user -d search_engine  # PostgreSQL CLI
docker compose exec -T postgres pg_dump -U search_user search_engine > backup.sql  # Backup
docker compose exec -T postgres psql -U search_user -d search_engine < backup.sql  # Restore
docker compose exec app php scripts/seed-database.php  # Import sample data

# === Development ===
docker compose exec app sh             # Access app container shell
docker compose exec app php -v         # Check PHP version
docker compose exec app php -m         # List PHP modules
docker compose exec postgres psql -U search_user -d search_engine -c "\dt"  # List tables

# === Cleanup ===
docker compose down                    # Stop services
docker compose down -v                 # Stop + remove volumes
docker system prune -a                 # Clean all unused Docker resources
```

---

## Local Development vs Docker

### Running Locally (without Docker for app)

If you want to run the PHP app locally but use Docker for PostgreSQL only:

1. **Update .env:**
```env
DB_HOST=localhost  # Instead of 'postgres'
```

2. **Start only PostgreSQL:**
```bash
docker compose up -d postgres
```

3. **Run PHP locally:**
```bash
php -S localhost:8000
```

### Running Everything in Docker (Recommended)

Use the default setup with `DB_HOST=postgres` - everything runs in containers.

---

## Migration Guide

**Docker setup is now the complete solution!**
- ✅ Full application stack in containers
- ✅ One command to start everything
- ✅ Isolated environment, no local PHP needed
- ✅ Live code reloading for development
- ✅ Easy setup, teardown, and reset
- ✅ Consistent across all systems
- ✅ Database and application together

---

**Project:** ITU/CSU07315 Search Engine  
**Stack:** PHP 8.2 + PostgreSQL 17 in Docker  
**Quick Start:** `docker compose up -d`  
**Access:** http://localhost:8000
