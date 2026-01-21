#!/bin/bash

# ===================================================
# PostgreSQL 17 Installation Script
# For Ubuntu/Debian Linux
# ===================================================

set -e  # Exit on error

echo "═══════════════════════════════════════════════════"
echo "  PostgreSQL 17 Installation Script"
echo "  Search Engine Project Setup"
echo "═══════════════════════════════════════════════════"
echo ""

# Check if running with sudo
if [ "$EUID" -ne 0 ]; then 
    echo "Please run with sudo: sudo bash scripts/install-postgresql.sh"
    exit 1
fi

echo "[1/6] Updating package lists..."
apt update -qq

echo "[2/6] Installing prerequisites..."
apt install -y wget ca-certificates gnupg lsb-release

echo "[3/6] Adding PostgreSQL repository..."
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /usr/share/keyrings/postgresql-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/postgresql-keyring.gpg] http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" | tee /etc/apt/sources.list.d/pgdg.list

echo "[4/6] Installing PostgreSQL 17..."
apt update -qq
apt install -y postgresql-17 postgresql-contrib-17

echo "[5/6] Installing PHP PostgreSQL extension..."
apt install -y php-pgsql

echo "[6/6] Starting PostgreSQL service..."
systemctl start postgresql
systemctl enable postgresql

echo ""
echo "═══════════════════════════════════════════════════"
echo "  ✓ PostgreSQL 17 installed successfully!"
echo "═══════════════════════════════════════════════════"
echo ""
echo "PostgreSQL version:"
sudo -u postgres psql --version
echo ""
echo "Next steps:"
echo "1. Run: sudo -u postgres psql"
echo "2. Create database and user (see README.md Step 3)"
echo "3. Run: psql -d search_engine -f scripts/setup-database.sql"
echo "4. Configure .env file with your credentials"
echo "5. Run: php scripts/seed-database.php"
echo "6. Start server: php -S localhost:8000"
echo ""
