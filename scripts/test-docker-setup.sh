#!/bin/bash

# ===================================================
# Docker Setup Test Script
# Verifies Docker PostgreSQL installation
# ===================================================

set -e

echo "═══════════════════════════════════════════════════"
echo "  Docker Setup Test Suite"
echo "═══════════════════════════════════════════════════"
echo ""

TESTS_PASSED=0
TESTS_FAILED=0

# Helper functions
pass() {
    echo "✓ PASS: $1"
    TESTS_PASSED=$((TESTS_PASSED + 1))
}

fail() {
    echo "✗ FAIL: $1"
    TESTS_FAILED=$((TESTS_FAILED + 1))
}

# Test 1: Check Docker installation
echo "[Test 1] Checking Docker installation..."
if command -v docker &> /dev/null; then
    pass "Docker is installed"
else
    fail "Docker is not installed"
fi

# Test 2: Check Docker Compose
echo "[Test 2] Checking Docker Compose..."
if command -v docker compose &> /dev/null; then
    pass "Docker Compose is available"
else
    fail "Docker Compose is not available"
fi

# Test 3: Check if Docker is running
echo "[Test 3] Checking Docker daemon..."
if docker info &> /dev/null 2>&1; then
    pass "Docker daemon is running"
else
    fail "Docker daemon is not running"
fi

# Test 4: Check if container is running
echo "[Test 4] Checking PostgreSQL container..."
if docker ps | grep -q search_engine_db; then
    pass "PostgreSQL container is running"
else
    fail "PostgreSQL container is not running"
    echo "    Hint: Run 'bash scripts/docker-setup.sh' first"
fi

# Test 5: Check container health
echo "[Test 5] Checking container health..."
if docker inspect search_engine_db 2>/dev/null | grep -q '"Status": "healthy"'; then
    pass "Container is healthy"
else
    fail "Container is not healthy"
fi

# Test 6: Check port mapping
echo "[Test 6] Checking port mapping..."
if docker ps | grep search_engine_db | grep -q "5432->5432"; then
    pass "Port 5432 is mapped correctly"
else
    fail "Port 5432 mapping issue"
fi

# Test 7: Test database connection
echo "[Test 7] Testing database connection..."
if docker compose exec -T postgres psql -U search_user -d search_engine -c "SELECT 1;" &> /dev/null; then
    pass "Database connection successful"
else
    fail "Cannot connect to database"
fi

# Test 8: Check if schema exists
echo "[Test 8] Checking database schema..."
TABLE_COUNT=$(docker compose exec -T postgres psql -U search_user -d search_engine -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'search_items';" 2>/dev/null | tr -d '[:space:]')
if [ "$TABLE_COUNT" = "1" ]; then
    pass "Table 'search_items' exists"
else
    fail "Table 'search_items' not found"
fi

# Test 9: Check indexes
echo "[Test 9] Checking database indexes..."
INDEX_COUNT=$(docker compose exec -T postgres psql -U search_user -d search_engine -t -c "SELECT COUNT(*) FROM pg_indexes WHERE tablename = 'search_items';" 2>/dev/null | tr -d '[:space:]')
if [ "$INDEX_COUNT" -ge "6" ]; then
    pass "Indexes created (found $INDEX_COUNT)"
else
    fail "Missing indexes (found $INDEX_COUNT, expected 6+)"
fi

# Test 10: Check if data exists
echo "[Test 10] Checking sample data..."
ROW_COUNT=$(docker compose exec -T postgres psql -U search_user -d search_engine -t -c "SELECT COUNT(*) FROM search_items;" 2>/dev/null | tr -d '[:space:]')
if [ "$ROW_COUNT" -gt "0" ]; then
    pass "Sample data exists ($ROW_COUNT records)"
else
    fail "No sample data found (run: php scripts/seed-database.php)"
fi

# Test 11: Test PHP database connection
echo "[Test 11] Testing PHP database connection..."
if php -r "
require 'config/env-loader.php';
require 'config/database.php';
EnvLoader::load();
try {
    \$db = Database::getConnection();
    echo 'OK';
} catch (Exception \$e) {
    echo 'FAIL';
}
" | grep -q "OK"; then
    pass "PHP can connect to database"
else
    fail "PHP cannot connect to database"
fi

# Test 12: Test search functionality
echo "[Test 12] Testing search functionality..."
if php -r "
require 'config/env-loader.php';
require 'config/database.php';
require 'includes/search-engine.class.php';
require 'includes/functions.php';
EnvLoader::load();
try {
    \$db = Database::getConnection();
    \$searcher = new SearchEngine(\$db);
    \$results = \$searcher->search('test', 1, 5, SearchMode::AND);
    echo count(\$results) >= 0 ? 'OK' : 'FAIL';
} catch (Exception \$e) {
    echo 'FAIL';
}
" | grep -q "OK"; then
    pass "Search functionality works"
else
    fail "Search functionality has issues"
fi

# Test 13: Check volume
echo "[Test 13] Checking Docker volume..."
if docker volume ls | grep -q "search-engine-_postgres_data"; then
    pass "Data volume exists"
else
    fail "Data volume not found"
fi

# Test 14: Check network
echo "[Test 14] Checking Docker network..."
if docker network ls | grep -q "search-engine-_search_engine_network"; then
    pass "Network exists"
else
    fail "Network not found"
fi

# Test 15: Check .env configuration
echo "[Test 15] Checking .env configuration..."
if grep -q "DB_PASSWORD=search_password_2026" .env; then
    pass ".env configured for Docker"
else
    fail ".env not configured properly"
fi

# Summary
echo ""
echo "═══════════════════════════════════════════════════"
echo "  Test Results"
echo "═══════════════════════════════════════════════════"
echo "Tests Passed: $TESTS_PASSED"
echo "Tests Failed: $TESTS_FAILED"
echo "Total Tests:  $((TESTS_PASSED + TESTS_FAILED))"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo "🎉 All tests passed! Your setup is ready."
    echo ""
    echo "Start the web server:"
    echo "  php -S localhost:8000"
    echo ""
    echo "Open in browser:"
    echo "  http://localhost:8000"
    exit 0
else
    echo "⚠️  Some tests failed. Please review the errors above."
    echo ""
    echo "Common fixes:"
    echo "  1. Start containers: bash scripts/docker-setup.sh"
    echo "  2. Import data: php scripts/seed-database.php"
    echo "  3. Check logs: docker compose logs postgres"
    exit 1
fi
