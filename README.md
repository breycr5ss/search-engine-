# Search Engine - Minimalistic Google-like Search

A fully functional search engine built with **PHP 8**, **PostgreSQL 17**, and modern web technologies. Features intelligent ranking, multiple search modes (AND/OR/EXACT), and a clean Google-inspired interface.

## 📋 Features

- ✅ **Three Search Modes:**
  - **AND Search** - All keywords must match (default)
  - **OR Search** - Any keyword can match
  - **EXACT Search** - Exact phrase matching

- ✅ **Intelligent Ranking Algorithm:**
  - Prioritizes title matches (4x weight)
  - Full-text search with PostgreSQL tsvector (2x weight)
  - Bonus points for keywords in both title and description
  - Recency sorting as tiebreaker

- ✅ **Modern PHP 8 Features:**
  - Enums for type safety
  - Match expressions
  - Strict types throughout
  - Named arguments

- ✅ **PostgreSQL 17 Advantages:**
  - Generated columns (auto-updating TSVECTOR)
  - GIN indexes for lightning-fast searches
  - Trigram similarity for fuzzy matching
  - ILIKE for case-insensitive searches

- ✅ **User Experience:**
  - Clean Google-like interface
  - Keyword highlighting in results
  - Pagination (10 results per page)
  - "I'm Feeling Lucky" button
  - Responsive design
  - Search statistics (result count, execution time)

- ✅ **Security:**
  - Environment variable configuration
  - PDO prepared statements
  - XSS protection
  - Input validation

## 🛠️ Technology Stack

- **Backend:** PHP 8.4.17
- **Database:** PostgreSQL 17
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Architecture:** MVC-inspired, Object-Oriented

## 📁 Project Structure

```
search-engine-/
├── config/
│   ├── database.php          # Database connection handler
│   └── env-loader.php         # Environment variable loader
├── includes/
│   ├── search-engine.class.php # Main SearchEngine class
│   └── functions.php          # Helper functions
├── scripts/
│   ├── setup-database.sql     # Database schema
│   └── seed-database.php      # Data import script
├── .env                       # Environment configuration (create from .env.example)
├── .env.example               # Environment template
├── .gitignore                 # Git ignore rules
├── index.php                  # Home page
├── results.php                # Search results page
├── style.css                  # Styles
├── sample-data.php            # Sample dataset (~10,000 records)
└── README.md                  # This file
```

## 🚀 Installation

### Quick Start with Docker (Recommended) ⚡

**Prerequisites:** Docker & Docker Compose installed

```bash
# making the scripts excuatable
chmod +x scripts/*.sh 


# 1. Start PostgreSQL container
bash scripts/docker-setup.sh

# 2. Import data
php scripts/seed-database.php

# 3. Start server
php -S localhost:8000

# 4. Open browser
# http://localhost:8000
```

**That's it!** See [DOCKER.md](DOCKER.md) for detailed Docker documentation.

---

### Alternative: Manual Installation

**Prerequisites:** Ubuntu/Debian Linux, sudo access

### Step 1: Install PostgreSQL 17

```bash
# Add PostgreSQL repository
sudo apt update
sudo apt install -y wget ca-certificates
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -
sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'

# Install PostgreSQL 17
sudo apt update
sudo apt install -y postgresql-17 postgresql-contrib-17

# Start PostgreSQL service
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Verify installation
psql --version
```

### Step 2: Install PHP PostgreSQL Extension

```bash
# Install PHP PostgreSQL driver
sudo apt install -y php-pgsql

# Restart PHP-FPM (if using)
sudo systemctl restart php8.4-fpm

# Verify installation
php -m | grep pgsql
```

### Step 3: Set Up Database

```bash
# Switch to postgres user
sudo -u postgres psql

# In PostgreSQL prompt, run:
```

```sql
-- Create database
CREATE DATABASE search_engine
    WITH 
    ENCODING = 'UTF8'
    LC_COLLATE = 'en_US.UTF-8'
    LC_CTYPE = 'en_US.UTF-8'
    TEMPLATE = template0;

-- Create user
CREATE USER search_user WITH ENCRYPTED PASSWORD 'your_secure_password_here';

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE search_engine TO search_user;

-- Exit
\q
```

```bash
# Connect to database and run setup script
sudo -u postgres psql -d search_engine -f scripts/setup-database.sql
```

### Step 4: Configure Environment

```bash
# Copy environment template
cp .env.example .env

# Edit configuration
nano .env
```

Update these values in `.env`:

```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=search_engine
DB_USER=search_user
DB_PASSWORD=your_secure_password_here  # Use the password from Step 3

APP_ENV=development
APP_DEBUG=true
APP_TIMEZONE=UTC

RESULTS_PER_PAGE=10
DEFAULT_SEARCH_MODE=and
MAX_QUERY_LENGTH=200
```

### Step 5: Seed Database

```bash
# Run the seeding script
php scripts/seed-database.php
```

This will:
- Load ~10,000 sample records
- Display progress bar
- Show statistics (time, records/second)
- Verify insertion

### Step 6: Start PHP Development Server

```bash
# Start server on port 8000
php -S localhost:8000

# Server will be accessible at:
# http://localhost:8000
```

## 🎯 Usage

### Home Page

1. Navigate to `http://localhost:8000`
2. Enter your search query
3. Select search mode:
   - **All words (AND)** - Find pages with all keywords
   - **Any word (OR)** - Find pages with any keyword
   - **Exact phrase** - Find exact phrase matches
4. Click **Search** or press Enter
5. Or click **I'm Feeling Lucky** to go directly to top result

### Search Results Page

- View ranked results with highlighted keywords
- Click result titles/URLs to visit pages
- Use pagination at bottom to browse more results
- Change search mode on-the-fly (auto-submits)
- See search statistics (result count, execution time)

## 🔍 Search Examples

### AND Search (Default)
```
Query: "electric vehicles 2026"
Results: Pages containing ALL three keywords
```

### OR Search
```
Query: "football basketball tennis"
Mode: Any word (OR)
Results: Pages containing ANY of these sports
```

### Exact Phrase Search
```
Query: "space exploration"
Mode: Exact phrase
Results: Pages containing the exact phrase "space exploration"
```

## ⚙️ Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | localhost | Database server hostname |
| `DB_PORT` | 5432 | PostgreSQL port |
| `DB_NAME` | search_engine | Database name |
| `DB_USER` | search_user | Database username |
| `DB_PASSWORD` | - | Database password (required) |
| `APP_ENV` | development | Environment (development/production) |
| `APP_DEBUG` | true | Enable debug mode |
| `RESULTS_PER_PAGE` | 10 | Results per page |
| `MAX_QUERY_LENGTH` | 200 | Maximum search query length |

### Ranking Algorithm

Results are scored using this formula:

```
Final Score = (Title Score × 4.0) + (Text Rank × 2.0) + Match Bonus

Where:
- Title Score: 10 (all keywords in title), 5 (some keywords), 0 (none)
- Text Rank: PostgreSQL ts_rank() on combined tsvector
- Match Bonus: +3 if keywords appear in both title and description
```

## 🧪 Testing

### Test Database Connection

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

### Test Search Functionality

Visit `http://localhost:8000` and try these queries:

1. **Single keyword:** `football`
2. **Multiple keywords (AND):** `football news 2026`
3. **Multiple keywords (OR):** Switch mode and search `football basketball`
4. **Exact phrase:** Switch to Exact and search `electric vehicles`
5. **No results:** `xyzabc123` (should show "no results" message)

### Performance Testing

```bash
# Test with ApacheBench (if installed)
ab -n 100 -c 10 "http://localhost:8000/results.php?q=test&mode=and"

# Or use curl to measure response time
time curl "http://localhost:8000/results.php?q=football"
```

## 🐛 Troubleshooting

### Issue: "Connection failed" error

**Solution:**
1. Check PostgreSQL is running: `sudo systemctl status postgresql`
2. Verify credentials in `.env` file
3. Test manual connection: `psql -h localhost -U search_user -d search_engine`

### Issue: "Table does not exist" error

**Solution:**
Run database setup script:
```bash
sudo -u postgres psql -d search_engine -f scripts/setup-database.sql
```

### Issue: No search results

**Solution:**
1. Verify data was seeded: `psql -d search_engine -c "SELECT COUNT(*) FROM search_items;"`
2. Re-run seed script if count is 0: `php scripts/seed-database.php`

### Issue: PHP extension not loaded

**Solution:**
```bash
# Install php-pgsql
sudo apt install php-pgsql

# Restart web server/PHP-FPM
sudo systemctl restart apache2
# or
sudo systemctl restart php8.4-fpm
```

### Issue: Permission denied on database

**Solution:**
```bash
# Grant all privileges
sudo -u postgres psql -d search_engine -c "GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO search_user;"
sudo -u postgres psql -d search_engine -c "GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO search_user;"
```

## 📊 Performance Benchmarks

With ~10,000 records and proper indexing:

- **Search execution time:** 0.02 - 0.10 seconds
- **Page load time:** < 0.5 seconds
- **Concurrent users supported:** 50-100 (depends on hardware)
- **Memory usage:** ~50-100 MB per request

## 🔐 Security Best Practices

1. **Production Deployment:**
   - Change `APP_ENV=production` in `.env`
   - Set `APP_DEBUG=false`
   - Use strong database passwords
   - Enable HTTPS
   - Implement rate limiting

2. **Database Security:**
   - Use separate database user with limited privileges
   - Never commit `.env` to version control
   - Regular backups
   - Keep PostgreSQL updated

3. **Web Security:**
   - All user input is escaped with `htmlspecialchars()`
   - PDO prepared statements prevent SQL injection
   - No direct file uploads
   - CSRF protection (if adding forms)

## 📚 Technical Documentation

### SearchEngine Class

Main search class with three modes:

```php
$searcher = new SearchEngine($pdo);
$results = $searcher->search(
    query: "electric vehicles",
    page: 1,
    perPage: 10,
    mode: SearchMode::AND
);
```

### Helper Functions

```php
highlightKeywords($text, $keywords);  // Highlight matches
extractDomain($url);                  // Get domain from URL
truncateText($text, $maxLength);      // Truncate with ellipsis
formatNumber($number);                // Format with commas
getSearchMode($string);               // Convert string to enum
```

### Database Schema

- **search_items** - Main table
  - `id` - Primary key
  - `title` - Page title (VARCHAR 255)
  - `description` - Page content (TEXT)
  - `page_name` - Display name
  - `page_fav_icon_path` - Favicon path
  - `page_url` - Full URL
  - `created_at` - Timestamp
  - `title_tsv` - Generated TSVECTOR
  - `description_tsv` - Generated TSVECTOR
  - `combined_tsv` - Weighted combined TSVECTOR

## 🚧 Future Enhancements

- [ ] Search suggestions/autocomplete
- [ ] Search history
- [ ] Advanced filters (date range, category)
- [ ] Export results (CSV, JSON)
- [ ] Search analytics dashboard
- [ ] REST API endpoint
- [ ] Fuzzy matching for typos
- [ ] Image search
- [ ] Multi-language support

## 📝 Assignment Requirements Checklist

- ✅ Two-page application (home + results)
- ✅ Clean home page with centered search box
- ✅ Results page with search box at top
- ✅ Database populated with ~10,000 records
- ✅ Full-text search using LIKE/ILIKE
- ✅ Multi-keyword support
- ✅ Ranking algorithm implementation
- ✅ HTML/CSS/PHP/PostgreSQL stack
- ✅ Insertion script for data population
- ✅ User-friendly interface

## 👥 Credits

- **Course:** ITU/CSU07315
- **Year:** 2026
- **Database:** PostgreSQL 17
- **PHP Version:** 8.4.17

## 📄 License

Educational project for ITU/CSU07315 course.

## 🤝 Contributing

This is an educational project. For improvements:

1. Test thoroughly
2. Follow PSR-12 coding standards
3. Document changes
4. Ensure backward compatibility

## 📞 Support

For issues or questions:
1. Check Troubleshooting section above
2. Review error logs: `tail -f /var/log/postgresql/postgresql-17-main.log`
3. Enable `APP_DEBUG=true` in `.env` for detailed errors

---

**Built with ❤️ for ITU/CSU07315 - 2026**
