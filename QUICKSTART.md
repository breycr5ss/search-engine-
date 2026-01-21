# QUICK START GUIDE

## Installation (5 Minutes)

### 1. Install PostgreSQL 17
```bash
sudo bash scripts/install-postgresql.sh
```

### 2. Configure Database Password
```bash
nano .env
# Edit DB_PASSWORD line with a secure password
```

### 3. Setup Database
```bash
bash scripts/setup-db.sh
```

### 4. Import Data (~10,000 records)
```bash
php scripts/seed-database.php
# Type 'yes' when prompted
# Wait ~5-10 seconds for import
```

### 5. Start Server
```bash
php -S localhost:8000
```

### 6. Open Browser
```
http://localhost:8000
```

---

## Manual Installation (Alternative)

If automated scripts don't work:

### Step 1: Install PostgreSQL
```bash
sudo apt update
sudo apt install -y postgresql-17 postgresql-contrib-17 php-pgsql
sudo systemctl start postgresql
```

### Step 2: Create Database
```bash
sudo -u postgres psql
```

In psql prompt:
```sql
CREATE DATABASE search_engine;
CREATE USER search_user WITH PASSWORD 'YourPassword123';
GRANT ALL PRIVILEGES ON DATABASE search_engine TO search_user;
\c search_engine
\i scripts/setup-database.sql
\q
```

### Step 3: Configure .env
```bash
cp .env.example .env
nano .env
# Set DB_PASSWORD to match above
```

### Step 4: Seed Database
```bash
php scripts/seed-database.php
```

### Step 5: Start
```bash
php -S localhost:8000
```

---

## Testing Your Installation

### Test 1: Home Page
- Open http://localhost:8000
- You should see "Search" logo and search box
- Three radio buttons: All words, Any word, Exact phrase

### Test 2: Simple Search
- Search for: `football`
- Should return multiple results
- Keywords should be highlighted
- Stats shown: "About X results (0.XX seconds)"

### Test 3: AND Search
- Search for: `football news 2026`
- Mode: All words (AND) - default
- Results must contain all three keywords

### Test 4: OR Search  
- Search for: `football basketball tennis`
- Change mode to: Any word (OR)
- Results can contain any of these sports

### Test 5: Exact Phrase
- Search for: `electric vehicles`
- Change mode to: Exact phrase
- Results must contain exact phrase

### Test 6: Pagination
- Any search with 10+ results
- Click page numbers at bottom
- Test Next/Previous links

### Test 7: I'm Feeling Lucky
- Enter: `football`
- Click "I'm Feeling Lucky"
- Should redirect to first result URL

---

## Troubleshooting

### Error: "Connection failed"
```bash
# Check PostgreSQL is running
sudo systemctl status postgresql

# Test connection manually
psql -h localhost -U search_user -d search_engine
# Enter your password from .env
```

### Error: "Table does not exist"
```bash
# Re-run setup
sudo -u postgres psql -d search_engine -f scripts/setup-database.sql
```

### Error: "No results found" for everything
```bash
# Check if data exists
psql -d search_engine -c "SELECT COUNT(*) FROM search_items;"

# If count is 0, re-run seeder
php scripts/seed-database.php
```

### Error: PHP extension not found
```bash
# Install php-pgsql
sudo apt install php-pgsql

# Verify
php -m | grep pgsql
```

---

## Configuration Tips

### Change Results Per Page
Edit `.env`:
```env
RESULTS_PER_PAGE=20  # Change from 10 to 20
```

### Enable Production Mode
Edit `.env`:
```env
APP_ENV=production
APP_DEBUG=false
```

### Increase Max Query Length
Edit `.env`:
```env
MAX_QUERY_LENGTH=500  # Default is 200
```

---

## File Locations

- **Home Page**: `index.php`
- **Results Page**: `results.php`
- **Styles**: `style.css`
- **Search Logic**: `includes/search-engine.class.php`
- **Database Config**: `config/database.php`
- **Environment**: `.env`
- **Documentation**: `README.md`

---

## Common Commands

```bash
# Start server
php -S localhost:8000

# Check PHP syntax
php -l index.php

# View error log
tail -f /var/log/postgresql/postgresql-17-main.log

# Connect to database
psql -d search_engine

# Count records
psql -d search_engine -c "SELECT COUNT(*) FROM search_items;"

# Reset database (careful!)
bash scripts/setup-db.sh
php scripts/seed-database.php
```

---

## Support

1. Check `README.md` for detailed documentation
2. Review error messages with `APP_DEBUG=true`
3. Check PostgreSQL logs
4. Verify `.env` configuration

---

**Project:** ITU/CSU07315 Search Engine
**Tech:** PHP 8 + PostgreSQL 17
**Author:** 2026
