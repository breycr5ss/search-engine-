#!/bin/bash

# ===================================================
# Docker-based PostgreSQL Setup Script
# Starts PostgreSQL 17 container and runs setup
# ===================================================

set -e  # Exit on error

echo "═══════════════════════════════════════════════════"
echo "  Docker PostgreSQL 17 Setup"
echo "  Search Engine Database"
echo "═══════════════════════════════════════════════════"
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Error: Docker is not installed"
    echo "Please install Docker first:"
    echo "  https://docs.docker.com/get-docker/"
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker compose &> /dev/null; then
    echo "❌ Error: Docker Compose is not installed"
    echo "Please install Docker Compose first:"
    echo "  https://docs.docker.com/compose/install/"
    exit 1
fi

echo "[1/5] Checking Docker status..."
if ! docker info &> /dev/null; then
    echo "❌ Error: Docker daemon is not running"
    echo "Please start Docker and try again"
    exit 1
fi
echo "✓ Docker is running"
echo ""

echo "[2/5] Stopping any existing containers..."
docker compose down 2>/dev/null || true
echo "✓ Cleaned up"
echo ""

echo "[3/5] Starting PostgreSQL 17 container..."
docker compose up -d postgres
echo "✓ Container started"
echo ""

echo "[4/5] Waiting for PostgreSQL to be ready..."
MAX_TRIES=30
COUNT=0
while [ $COUNT -lt $MAX_TRIES ]; do
    if docker compose exec -T postgres pg_isready -U search_user -d search_engine &> /dev/null; then
        echo "✓ PostgreSQL is ready"
        break
    fi
    COUNT=$((COUNT + 1))
    if [ $COUNT -eq $MAX_TRIES ]; then
        echo "❌ Error: PostgreSQL did not start in time"
        docker compose logs postgres
        exit 1
    fi
    echo -n "."
    sleep 1
done
echo ""
echo ""

echo "[5/5] Verifying database setup..."
RESULT=$(docker compose exec -T postgres psql -U search_user -d search_engine -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'search_items';" 2>/dev/null || echo "0")
RESULT=$(echo $RESULT | tr -d '[:space:]')

if [ "$RESULT" = "1" ]; then
    echo "✓ Database schema created successfully"
else
    echo "⚠ Schema not found, creating manually..."
    docker compose exec -T postgres psql -U search_user -d search_engine < scripts/setup-database.sql
    echo "✓ Schema created"
fi
echo ""

echo "═══════════════════════════════════════════════════"
echo "  ✓ Docker PostgreSQL Setup Complete!"
echo "═══════════════════════════════════════════════════"
echo ""
echo "Container Status:"
docker compose ps
echo ""
echo "Database Connection Info:"
echo "  Host: localhost"
echo "  Port: 5432"
echo "  Database: search_engine"
echo "  User: search_user"
echo "  Password: search_password_2026"
echo ""
echo "Next steps:"
echo "  1. php scripts/seed-database.php  (import data)"
echo "  2. php -S localhost:8000          (start web server)"
echo ""
echo "Optional commands:"
echo "  docker compose logs postgres       (view logs)"
echo "  docker compose exec postgres psql -U search_user -d search_engine  (connect to DB)"
echo "  docker compose down                (stop containers)"
echo "  docker compose --profile tools up  (start with pgAdmin)"
echo ""
