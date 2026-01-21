<?php
declare(strict_types=1);

/**
 * Search Engine Home Page
 * Simple Google-like search interface
 */

require_once __DIR__ . '/config/env-loader.php';

// Load environment variables
try {
    EnvLoader::load();
} catch (RuntimeException $e) {
    // Continue without .env (use defaults)
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Search • Home</title>
    <link rel="stylesheet" href="style.css" type="text/css"/>
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
    <meta name="description" content="Search anything with our simple and fast search engine." />
    <meta name="keywords" content="search, engine, simple, fast, web search" />
    <meta name="author" content="Your Name" />
    <meta name="theme-color" content="#ffffff" />
    <link rel="apple-touch-icon" href="apple-touch-icon.png" />
    <link rel="manifest" href="manifest.json" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" />
</head>

<body>

    <div class="container">
        <h1 class="home-logo">Search</h1>

        <form class="home-search-form" action="results.php" method="get">
            <span class="search-icon">🔍</span>
            <input type="search" name="q" class="search-input" 
                   placeholder="Search anything..." autocomplete="off" 
                   autofocus required>
            
            <div class="search-mode-toggle">
                <span class="search-mode-label">Search Mode:</span>
                <label>
                    <input type="radio" name="mode" value="and" checked>
                    All words (AND)
                </label>
                <label>
                    <input type="radio" name="mode" value="or">
                    Any word (OR)
                </label>
                <label>
                    <input type="radio" name="mode" value="exact">
                    Exact phrase
                </label>
            </div>

            <div class="buttons">
                <button type="submit" class="btn btn-primary">Search</button>
                <button type="submit" name="lucky" value="1" class="btn">I'm Feeling Lucky</button>
            </div>
        </form>
    </div>

    <footer>
        © 2026 — ITU/CSU07315
    </footer>

</body>

</html>
