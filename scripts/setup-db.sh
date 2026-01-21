#!/bin/bash

# ===================================================
# Database Setup Script
# Creates database, user, and runs schema
# ===================================================

set -e

echo "═══════════════════════════════════════════════════"
echo "  Database Setup Script"
echo "  Creating database and tables"
echo "═══════════════════════════════════════════════════"
echo ""

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "Error: .env file not found!"
    echo "Please copy .env.example to .env and configure it first."
    exit 1
fi

# Read credentials from .env
source <(grep -v '^#' .env | sed 's/\r$//')

echo "Using configuration:"
echo "  Database: $DB_NAME"
echo "  User: $DB_USER"
echo "  Host: $DB_HOST"
echo ""

echo "Enter PostgreSQL admin password when prompted..."
echo ""

# Create database and user
echo "[1/3] Creating database and user..."
sudo -u postgres psql << EOF
-- Create database
DROP DATABASE IF EXISTS $DB_NAME;
CREATE DATABASE $DB_NAME
    WITH 
    ENCODING = 'UTF8'
    LC_COLLATE = 'en_US.UTF-8'
    LC_CTYPE = 'en_US.UTF-8'
    TEMPLATE = template0;

-- Create user
DROP USER IF EXISTS $DB_USER;
CREATE USER $DB_USER WITH ENCRYPTED PASSWORD '$DB_PASSWORD';

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;

-- Connect and grant schema privileges
\c $DB_NAME
GRANT ALL PRIVILEGES ON SCHEMA public TO $DB_USER;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO $DB_USER;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO $DB_USER;

\q
EOF

echo "✓ Database and user created"
echo ""

# Run schema setup
echo "[2/3] Creating tables and indexes..."
sudo -u postgres psql -d $DB_NAME -f scripts/setup-database.sql

echo "✓ Tables and indexes created"
echo ""

# Verify
echo "[3/3] Verifying setup..."
RESULT=$(sudo -u postgres psql -d $DB_NAME -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'search_items';")

if [ "$RESULT" -eq 1 ]; then
    echo "✓ Verification successful"
else
    echo "✗ Verification failed - table not found"
    exit 1
fi

echo ""
echo "═══════════════════════════════════════════════════"
echo "  ✓ Database setup completed!"
echo "═══════════════════════════════════════════════════"
echo ""
echo "Next step:"
echo "  php scripts/seed-database.php"
echo ""
