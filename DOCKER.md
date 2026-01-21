# DOCKER SETUP GUIDE

## Quick Start with Docker (Recommended)

### Prerequisites
- Docker installed
- Docker Compose installed

### Installation (3 Minutes)

#### 1. Start PostgreSQL Container
```bash
# Automated setup (recommended)
bash scripts/docker-setup.sh

# Or manual:
docker compose up -d postgres
```

#### 2. Wait for PostgreSQL (automatic)
The script waits for PostgreSQL to be ready. Or check manually:
```bash
docker compose ps
# Should show "healthy" status
```

#### 3. Import Data
```bash
php scripts/seed-database.php
# Type 'yes' when prompted
# Wait ~5-10 seconds for import
```

#### 4. Start Web Server
```bash
php -S localhost:8000
```

#### 5. Open Browser
```
http://localhost:8000
```

---

## Docker Compose Configuration

### Services Included

#### 1. PostgreSQL 17 (Main Database)
- **Image:** `postgres:17`
- **Container:** `search_engine_db`
- **Port:** `5432` (mapped to host)
- **Auto-start:** Yes
- **Health check:** Built-in
- **Data persistence:** Docker volume `postgres_data`

**Credentials:**
- Database: `search_engine`
- User: `search_user`
- Password: `search_password_2026`

#### 2. pgAdmin 4 (Optional Database UI)
- **Image:** `dpage/pgadmin4:latest`
- **Container:** `search_engine_pgadmin`
- **Port:** `8080` (mapped to host)
- **Auto-start:** No (use `--profile tools`)

**Credentials:**
- Email: `admin@searchengine.local`
- Password: `admin123`

---

## Docker Commands

### Container Management

```bash
# Start all services
docker compose up -d

# Start with pgAdmin
docker compose --profile tools up -d

# Stop all services
docker compose down

# Stop and remove volumes (data loss!)
docker compose down -v

# View running containers
docker compose ps

# View logs
docker compose logs postgres
docker compose logs -f postgres  # Follow logs

# Restart PostgreSQL
docker compose restart postgres
```

### Database Access

```bash
# Connect to PostgreSQL CLI
docker compose exec postgres psql -U search_user -d search_engine

# Run SQL file
docker compose exec -T postgres psql -U search_user -d search_engine < scripts/setup-database.sql

# Backup database
docker compose exec -T postgres pg_dump -U search_user search_engine > backup.sql

# Restore database
docker compose exec -T postgres psql -U search_user -d search_engine < backup.sql

# Check database size
docker compose exec postgres psql -U search_user -d search_engine -c "SELECT pg_size_pretty(pg_database_size('search_engine'));"
```

### Container Inspection

```bash
# View container stats
docker stats search_engine_db

# Check health status
docker compose ps
docker inspect search_engine_db | grep -A 5 Health

# View container details
docker compose exec postgres pg_isready -U search_user
docker compose exec postgres psql -U search_user -d search_engine -c "SELECT version();"
```

---

## pgAdmin Setup (Optional)

### 1. Start pgAdmin
```bash
docker compose --profile tools up -d
```

### 2. Open pgAdmin
```
http://localhost:8080
```

### 3. Login
- Email: `admin@searchengine.local`
- Password: `admin123`

### 4. Add Server
1. Right-click "Servers" → "Register" → "Server"
2. **General Tab:**
   - Name: `Search Engine DB`
3. **Connection Tab:**
   - Host: `postgres` (container name)
   - Port: `5432`
   - Database: `search_engine`
   - Username: `search_user`
   - Password: `search_password_2026`
4. Click "Save"

---

## Configuration Files

### docker-compose.yml
Main configuration file defining:
- PostgreSQL 17 service
- pgAdmin service
- Networks
- Volumes
- Health checks

### .env
Application configuration:
```env
DB_HOST=localhost      # Use 'postgres' if app runs in container
DB_PORT=5432
DB_NAME=search_engine
DB_USER=search_user
DB_PASSWORD=search_password_2026
```

---

## Volume Management

### List Volumes
```bash
docker volume ls | grep search-engine
```

### Inspect Volume
```bash
docker volume inspect search-engine-_postgres_data
```

### Backup Volume
```bash
# Create backup directory
mkdir -p backups

# Backup data
docker compose exec -T postgres pg_dump -U search_user search_engine | gzip > backups/backup-$(date +%Y%m%d-%H%M%S).sql.gz
```

### Restore from Backup
```bash
# Stop containers
docker compose down

# Start fresh
docker compose up -d postgres

# Wait for ready
sleep 10

# Restore
gunzip -c backups/backup-20260121-120000.sql.gz | \
  docker compose exec -T postgres psql -U search_user -d search_engine
```

---

## Troubleshooting

### Issue: Container won't start
```bash
# Check logs
docker compose logs postgres

# Check if port is in use
sudo netstat -tulpn | grep 5432

# Kill existing PostgreSQL
sudo systemctl stop postgresql

# Try again
docker compose up -d postgres
```

### Issue: Connection refused
```bash
# Check container status
docker compose ps

# Check health
docker inspect search_engine_db | grep -A 10 Health

# Wait for healthy status
while [ "$(docker inspect -f {{.State.Health.Status}} search_engine_db)" != "healthy" ]; do
    echo "Waiting for PostgreSQL..."
    sleep 2
done
```

### Issue: Permission denied
```bash
# Fix volume permissions
docker compose down
docker volume rm search-engine-_postgres_data
docker compose up -d
```

### Issue: Schema not created
```bash
# Run setup manually
docker compose exec -T postgres psql -U search_user -d search_engine < scripts/setup-database.sql

# Verify
docker compose exec postgres psql -U search_user -d search_engine -c "\dt"
```

---

## Performance Tuning

### PostgreSQL Configuration
Edit `docker-compose.yml` to add environment variables:

```yaml
environment:
  POSTGRES_INITDB_ARGS: "--encoding=UTF8"
  # Add these for better performance:
  POSTGRES_MAX_CONNECTIONS: "100"
  POSTGRES_SHARED_BUFFERS: "256MB"
  POSTGRES_EFFECTIVE_CACHE_SIZE: "1GB"
  POSTGRES_WORK_MEM: "16MB"
```

### Resource Limits
Add to `docker-compose.yml` under `postgres` service:

```yaml
deploy:
  resources:
    limits:
      cpus: '2'
      memory: 2G
    reservations:
      cpus: '1'
      memory: 1G
```

---

## Production Deployment

### Security Improvements

1. **Change default passwords:**
```yaml
environment:
  POSTGRES_PASSWORD: "${DB_PASSWORD}"  # Use env variable
```

2. **Remove port mapping:**
```yaml
# Comment out if app runs in same network
# ports:
#   - "5432:5432"
```

3. **Use secrets:**
```yaml
secrets:
  db_password:
    file: ./secrets/db_password.txt
environment:
  POSTGRES_PASSWORD_FILE: /run/secrets/db_password
```

### Backup Strategy
```bash
# Add to crontab
0 2 * * * cd /path/to/project && docker compose exec -T postgres pg_dump -U search_user search_engine | gzip > backups/backup-$(date +\%Y\%m\%d).sql.gz
```

---

## Monitoring

### View Container Metrics
```bash
# CPU, Memory, Network
docker stats search_engine_db

# Detailed info
docker compose top postgres
```

### PostgreSQL Metrics
```bash
# Connection count
docker compose exec postgres psql -U search_user -d search_engine -c \
  "SELECT count(*) FROM pg_stat_activity;"

# Database size
docker compose exec postgres psql -U search_user -d search_engine -c \
  "SELECT pg_size_pretty(pg_database_size('search_engine'));"

# Table statistics
docker compose exec postgres psql -U search_user -d search_engine -c \
  "SELECT schemaname,relname,n_live_tup FROM pg_stat_user_tables;"
```

---

## Testing

### 1. Test Container Health
```bash
docker compose ps
# Should show "healthy" status
```

### 2. Test Database Connection
```bash
docker compose exec postgres psql -U search_user -d search_engine -c "SELECT 1;"
```

### 3. Test from PHP
```bash
php -r "
require 'config/env-loader.php';
require 'config/database.php';
EnvLoader::load();
\$db = Database::getConnection();
echo 'Connection successful!\n';
"
```

### 4. Test Search
```bash
php -S localhost:8000 &
curl "http://localhost:8000/results.php?q=football"
```

---

## Complete Command Reference

```bash
# Setup
bash scripts/docker-setup.sh          # Automated setup
docker compose up -d postgres          # Start PostgreSQL
php scripts/seed-database.php          # Import data
php -S localhost:8000                  # Start web server

# Management
docker compose ps                      # Status
docker compose logs -f postgres        # Logs
docker compose restart postgres        # Restart
docker compose down                    # Stop
docker compose down -v                 # Stop + delete data

# Database
docker compose exec postgres psql -U search_user -d search_engine  # CLI
docker compose exec -T postgres pg_dump -U search_user search_engine > backup.sql  # Backup

# pgAdmin
docker compose --profile tools up -d   # Start with pgAdmin
open http://localhost:8080             # Open pgAdmin

# Health checks
docker inspect search_engine_db        # Container details
docker stats search_engine_db          # Resource usage
```

---

## Migration from Local PostgreSQL

If you already have data in a local PostgreSQL:

```bash
# 1. Export from local
pg_dump -U search_user search_engine > migration.sql

# 2. Start Docker PostgreSQL
docker compose up -d postgres

# 3. Import to Docker
docker compose exec -T postgres psql -U search_user -d search_engine < migration.sql

# 4. Update .env (already done)
# DB_HOST=localhost (not 'postgres' - we access from host)

# 5. Test
php -r "require 'config/database.php'; Database::getConnection();"
```

---

**Docker setup is the recommended approach!**
- Isolated environment
- Easy setup and teardown
- Consistent across systems
- No system-wide installation
- Easy backup and restore

---

**Project:** ITU/CSU07315 Search Engine  
**Database:** PostgreSQL 17 in Docker  
**Quick Start:** `bash scripts/docker-setup.sh`
