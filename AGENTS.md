# AGENTS.md - Search Engine Project Guidelines

This document provides guidelines for agentic coding agents working on the Search Engine project. It covers build commands, testing, code style, and development practices.

## Project Overview

A PHP 8.4.17 search engine with PostgreSQL 17 backend, featuring full-text search with three modes (AND/OR/EXACT), intelligent ranking, and a clean Google-inspired interface.

## Build, Lint, and Test Commands

### Development Server
```bash
# Start PHP development server
php -S localhost:8000

# With Docker (recommended for full setup)
bash scripts/docker-setup.sh
php scripts/seed-database.php
php -S localhost:8000
```

### Database Setup
```bash
# PostgreSQL setup (Ubuntu/Debian)
sudo apt install postgresql-17 php-pgsql
sudo -u postgres psql -f scripts/setup-database.sql
php scripts/seed-database.php
```

### Testing Commands

#### Database Connection Test
```bash
php -r "
require 'config/env-loader.php';
require 'config/database.php';
EnvLoader::load();
try {
    \$db = Database::getConnection();
    echo 'Connection successful!\n';
} catch (Exception \$e) {
    echo 'Connection failed: ' . \$e->getMessage() . '\n';
}
"
```

#### Manual Search Testing
```bash
# Test search functionality via browser at localhost:8000
# Try these queries:
# - Single keyword: "football"
# - Multiple keywords (AND): "football news 2026"
# - Multiple keywords (OR): "football basketball"
# - Exact phrase: "electric vehicles"
# - No results: "xyzabc123"
```

#### Performance Testing
```bash
# Load testing with Apache Bench
ab -n 100 -c 10 "http://localhost:8000/results.php?q=test&mode=and"

# Response time testing
time curl "http://localhost:8000/results.php?q=football"
```

### Code Quality (No Automated Linting Configured)
```bash
# Manual PHP syntax check
php -l includes/search-engine.class.php
php -l includes/functions.php
php -l config/database.php
php -l index.php
php -l results.php

# Check for common issues
find . -name "*.php" -exec php -l {} \;
```

## Code Style Guidelines

### PHP Standards
- **Strict Types**: Always use `declare(strict_types=1);` at the top of PHP files
- **PHP 8 Features**: Utilize enums, match expressions, named arguments, and union types
- **PSR-12 Compliance**: Follow PHP-FIG standards for code formatting
- **DocBlocks**: Use comprehensive PHPDoc comments for all classes, methods, and functions

### Naming Conventions
- **Classes**: PascalCase (e.g., `SearchEngine`, `Database`)
- **Methods/Functions**: camelCase (e.g., `search()`, `getConnection()`, `highlightKeywords()`)
- **Constants/Enums**: UPPER_SNAKE_CASE (e.g., `SearchMode::AND`)
- **Variables**: camelCase (e.g., `$searchQuery`, `$totalResults`)
- **Files**: kebab-case for multi-word files (e.g., `search-engine.class.php`)

### File Structure
```
search-engine-/
├── config/           # Configuration files
│   ├── database.php  # Database connection handler
│   └── env-loader.php # Environment variable loader
├── includes/         # Core classes and utilities
│   ├── search-engine.class.php # Main SearchEngine class
│   └── functions.php # Helper functions
├── scripts/          # Setup and maintenance scripts
├── index.php         # Home page
├── results.php       # Search results page
└── style.css         # Stylesheet
```

### Import/Require Statements
- Use `require_once` for critical dependencies
- Group imports logically (config first, then utilities)
- Use absolute paths with `__DIR__` for reliability

### Type Declarations
```php
// Good: Full type declarations
function search(string $query, int $page = 1, SearchMode $mode = SearchMode::AND): array

// Good: Union types where appropriate
function processResult(?string $title, ?string $description): string

// Good: Proper return types
function getConnection(): PDO
```

### Error Handling
```php
// Good: Specific exceptions with context
try {
    $db = Database::getConnection();
} catch (PDOException $e) {
    throw new RuntimeException("Database connection failed: " . $e->getMessage());
}

// Good: Graceful degradation in production
if (EnvLoader::getBool('APP_DEBUG', false)) {
    throw new RuntimeException($detailedError);
} else {
    throw new RuntimeException("Operation failed. Please try again.");
}
```

### Database Operations
```php
// Good: PDO with prepared statements
$stmt = $this->db->prepare("SELECT * FROM search_items WHERE id = ?");
$stmt->execute([$id]);
$result = $stmt->fetch();

// Good: Proper PDO configuration
$connection = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
```

### Security Practices
- **Input Validation**: Always validate and sanitize user inputs
- **XSS Protection**: Use `htmlspecialchars()` for HTML output
- **SQL Injection**: Use prepared statements exclusively
- **Environment Variables**: Store secrets in `.env` files, never in code
- **Error Messages**: Don't expose sensitive information in production

### HTML/CSS/JavaScript Standards
```html
<!-- Good: Semantic HTML with proper attributes -->
<form class="search-form" action="results.php" method="get">
    <input type="search" name="q" required autocomplete="off">
    <button type="submit">Search</button>
</form>
```

```css
/* Good: BEM-like naming and organization */
.search-form {
    /* Styles */
}

.search-form__input {
    /* Input styles */
}

.search-form__button {
    /* Button styles */
}
```

### Performance Considerations
- **Database Queries**: Use appropriate indexes and query optimization
- **Memory Usage**: Be mindful of large result sets
- **Caching**: Consider implementing query result caching for frequently accessed data
- **Lazy Loading**: Load resources only when needed

### Testing Approach
- **Manual Testing**: Primary testing method due to lack of automated test framework
- **Integration Testing**: Test full search workflows end-to-end
- **Edge Cases**: Test with empty queries, special characters, very long queries
- **Performance Testing**: Monitor query execution time and memory usage

### Commit Message Format
```
feat: add fuzzy search capability
fix: resolve pagination bug on large result sets
docs: update installation instructions
refactor: simplify ranking algorithm
```

### Environment Configuration
Key environment variables (defined in `.env`):
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`
- `APP_ENV`, `APP_DEBUG`, `APP_TIMEZONE`
- `RESULTS_PER_PAGE`, `DEFAULT_SEARCH_MODE`, `MAX_QUERY_LENGTH`

### Deployment Considerations
- **Production**: Set `APP_ENV=production` and `APP_DEBUG=false`
- **Security**: Use strong database passwords and enable HTTPS
- **Performance**: Monitor PostgreSQL query performance and consider connection pooling
- **Backups**: Regular database backups for production deployments

### Database Schema Notes
- Uses PostgreSQL 17 features (generated columns, TSVECTOR)
- Full-text search with weighted ranking
- GIN indexes for optimal search performance
- Trigram similarity for fuzzy matching capabilities

## Common Development Tasks

### Adding New Features
1. Update database schema if needed (`scripts/setup-database.sql`)
2. Modify SearchEngine class for core functionality
3. Add helper functions to `includes/functions.php`
4. Update UI in appropriate PHP files
5. Test thoroughly with various search scenarios

### Bug Fixes
1. Reproduce the issue
2. Identify root cause (check database queries, PHP logic, UI rendering)
3. Implement fix with proper error handling
4. Test edge cases and regression scenarios
5. Update any affected documentation

### Performance Optimization
1. Profile current performance (use `microtime()` for timing)
2. Identify bottlenecks (database queries, PHP processing, frontend rendering)
3. Optimize queries, add indexes if needed
4. Implement caching where appropriate
5. Test performance improvements

This document should be updated when new tools, frameworks, or significant code style changes are introduced to the project.</content>
<parameter name="filePath">/home/lemasani/Projects/Trials/search-engine-/AGENTS.md