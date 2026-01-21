<?php
declare(strict_types=1);

/**
 * Search Results Page
 * Displays search results with ranking and pagination
 */

require_once __DIR__ . '/config/env-loader.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/search-engine.class.php';
require_once __DIR__ . '/includes/functions.php';

// Load environment variables
try {
    EnvLoader::load();
} catch (RuntimeException $e) {
    // Continue with defaults
}

// Get search parameters
$query = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$modeStr = $_GET['mode'] ?? 'and';
$mode = getSearchMode($modeStr);

// Handle "I'm Feeling Lucky"
if (isset($_GET['lucky']) && !empty($query)) {
    try {
        $db = Database::getConnection();
        $searcher = new SearchEngine($db);
        $results = $searcher->search($query, 1, 1, $mode);
        
        if (!empty($results) && !empty($results[0]['page_url'])) {
            redirect($results[0]['page_url']);
        }
    } catch (Exception $e) {
        logMessage("Lucky search error: " . $e->getMessage(), 'ERROR');
    }
}

// Redirect if no query
if (empty($query)) {
    redirect('index.php');
}

// Initialize variables
$results = [];
$totalResults = 0;
$executionTime = 0;
$error = null;

// Perform search
try {
    $db = Database::getConnection();
    $searcher = new SearchEngine($db);
    $perPage = EnvLoader::getInt('RESULTS_PER_PAGE', 10);
    
    $results = $searcher->search($query, $page, $perPage, $mode);
    $totalResults = $searcher->getTotalResults();
    $executionTime = $searcher->getExecutionTime();
    
} catch (Exception $e) {
    $error = "Search failed. Please try again later.";
    logMessage("Search error: " . $e->getMessage(), 'ERROR');
    
    if (EnvLoader::getBool('APP_DEBUG', false)) {
        $error .= " (" . $e->getMessage() . ")";
    }
}

// Pagination calculations
$perPage = EnvLoader::getInt('RESULTS_PER_PAGE', 10);
$totalPages = (int) ceil($totalResults / $perPage);
$pages = generatePagination($page, $totalPages);

// Extract keywords for highlighting
$keywords = extractSearchKeywords($query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= e($query) ?> - Search Results</title>
    <link rel="stylesheet" href="style.css" type="text/css"/>
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
    <meta name="description" content="Search results for <?= e($query) ?>" />
    <meta name="robots" content="noindex, nofollow" />
</head>
<body>

<header>
    <div class="search-bar-container">
        <a href="index.php" class="logo">Search</a>

        <form class="search-form" action="results.php" method="get" style="flex: 1;">
            <span class="search-icon">🔍</span>
            <input 
                type="search" 
                name="q" 
                class="search-input" 
                value="<?= e($query) ?>" 
                placeholder="Search..."
                required
                style="width: 100%;"
            >
            
            <div class="results-search-mode">
                <label title="All keywords must match">
                    <input type="radio" name="mode" value="and" 
                           <?= $mode === SearchMode::AND ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    All
                </label>
                <label title="Any keyword can match">
                    <input type="radio" name="mode" value="or"
                           <?= $mode === SearchMode::OR ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    Any
                </label>
                <label title="Exact phrase match">
                    <input type="radio" name="mode" value="exact"
                           <?= $mode === SearchMode::EXACT ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    Exact
                </label>
            </div>
        </form>
    </div>
</header>

<main>
    <?php if ($error): ?>
        <div class="error-message">
            <?= e($error) ?>
        </div>
    <?php else: ?>
        <div class="stats">
            About <?= formatNumber($totalResults) ?> result<?= $totalResults !== 1 ? 's' : '' ?>
            (<?= number_format($executionTime, 3) ?> seconds)
            
            <?php if ($mode !== SearchMode::AND): ?>
                <span style="color: var(--primary); font-weight: 500;">
                    • <?= match($mode) {
                        SearchMode::OR => 'OR Search',
                        SearchMode::EXACT => 'Exact Phrase',
                        default => ''
                    } ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if (empty($results)): ?>
            <div class="no-results">
                <h2>No results found for "<?= e($query) ?>"</h2>
                <p>Your search did not match any documents.</p>
                <ul>
                    <li>Make sure all words are spelled correctly</li>
                    <li>Try different or more general keywords</li>
                    <li>Try fewer keywords</li>
                    <?php if ($mode === SearchMode::AND): ?>
                        <li>Switch to <strong>Any word (OR)</strong> search mode</li>
                    <?php endif; ?>
                    <?php if ($mode === SearchMode::EXACT): ?>
                        <li>Try without quotes for broader results</li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php else: ?>
            
            <?php foreach ($results as $result): ?>
            <div class="result-item">
                <div class="result-header">
                    <img src="<?= e($result['page_fav_icon_path']) ?>" 
                         class="favicon" 
                         alt="<?= e($result['page_name']) ?>" 
                         loading="lazy"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2716%27 height=%2716%27%3E%3Crect width=%2716%27 height=%2716%27 fill=%27%23ddd%27/%3E%3C/svg%3E'">
                    <a href="<?= e($result['page_url']) ?>" 
                       class="result-url" 
                       target="_blank"
                       rel="noopener noreferrer">
                        <?= e(extractDomain($result['page_url'])) ?>
                    </a>
                </div>
                
                <h3 class="result-title">
                    <a href="<?= e($result['page_url']) ?>" 
                       target="_blank"
                       rel="noopener noreferrer">
                        <?= highlightKeywords(e($result['title']), $keywords) ?>
                    </a>
                </h3>
                
                <div class="result-snippet">
                    <?= highlightKeywords(
                        e(truncateText($result['description'] ?? '', 200)), 
                        $keywords
                    ) ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?q=<?= urlencode($query) ?>&mode=<?= $modeStr ?>&page=<?= $page - 1 ?>" 
                       class="page-link">‹ Previous</a>
                <?php endif; ?>
                
                <?php foreach ($pages as $p): ?>
                    <?php if ($p === '...'): ?>
                        <span class="page-link disabled">...</span>
                    <?php elseif ($p === $page): ?>
                        <span class="page-link current"><?= $p ?></span>
                    <?php else: ?>
                        <a href="?q=<?= urlencode($query) ?>&mode=<?= $modeStr ?>&page=<?= $p ?>" 
                           class="page-link"><?= $p ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?q=<?= urlencode($query) ?>&mode=<?= $modeStr ?>&page=<?= $page + 1 ?>" 
                       class="page-link">Next ›</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
        <?php endif; ?>
    <?php endif; ?>

</main>

<script>
// Optional: Auto-submit form on mode change (already handled with onchange)
// Optional: Save search preferences to localStorage
document.addEventListener('DOMContentLoaded', function() {
    // Remember last search mode
    const modeInputs = document.querySelectorAll('input[name="mode"]');
    modeInputs.forEach(input => {
        input.addEventListener('change', function() {
            localStorage.setItem('searchMode', this.value);
        });
    });
});
</script>

</body>
</html>
