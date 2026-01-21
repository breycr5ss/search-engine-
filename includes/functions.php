<?php
declare(strict_types=1);

/**
 * Helper Functions for Search Engine
 * PHP 8 compatible utility functions
 */

/**
 * Highlight search keywords in text with HTML span
 * 
 * @param string $text Text to highlight
 * @param array $keywords Keywords to highlight
 * @return string Text with highlighted keywords
 */
function highlightKeywords(string $text, array $keywords): string {
    if (empty($keywords)) {
        return $text;
    }
    
    foreach ($keywords as $keyword) {
        if (strlen($keyword) < 2) continue;
        
        $pattern = '/(' . preg_quote($keyword, '/') . ')/i';
        $text = preg_replace(
            $pattern,
            '<span class="highlight">$1</span>',
            $text
        );
    }
    
    return $text;
}

/**
 * Extract domain name from URL
 * 
 * @param string $url Full URL
 * @return string Domain name without www
 */
function extractDomain(string $url): string {
    $parsed = parse_url($url);
    $host = $parsed['host'] ?? '';
    
    // Remove www. prefix
    return str_starts_with($host, 'www.') 
        ? substr($host, 4) 
        : $host;
}

/**
 * Truncate text to specified length with ellipsis
 * 
 * @param string $text Text to truncate
 * @param int $maxLength Maximum length
 * @return string Truncated text
 */
function truncateText(string $text, int $maxLength = 200): string {
    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }
    
    $truncated = mb_substr($text, 0, $maxLength);
    $lastSpace = mb_strrpos($truncated, ' ');
    
    return $lastSpace !== false 
        ? mb_substr($truncated, 0, $lastSpace) . '...'
        : $truncated . '...';
}

/**
 * Escape HTML for safe output (alias for htmlspecialchars)
 * 
 * @param mixed $value Value to escape
 * @return string Escaped HTML
 */
function e(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Format large numbers with thousands separator
 * 
 * @param int $number Number to format
 * @return string Formatted number
 */
function formatNumber(int $number): string {
    return number_format($number);
}

/**
 * Get SearchMode enum from string
 * 
 * @param string|null $mode Mode string ('and', 'or', 'exact')
 * @return SearchMode Search mode enum
 */
function getSearchMode(?string $mode): SearchMode {
    return match(strtolower($mode ?? '')) {
        'or' => SearchMode::OR,
        'exact' => SearchMode::EXACT,
        default => SearchMode::AND,
    };
}

/**
 * Generate pagination array for display
 * 
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param int $delta Number of pages to show on each side
 * @return array Array of page numbers and ellipsis
 */
function generatePagination(int $currentPage, int $totalPages, int $delta = 2): array {
    if ($totalPages <= 1) {
        return [];
    }
    
    $pages = [];
    $left = max(1, $currentPage - $delta);
    $right = min($totalPages, $currentPage + $delta);
    
    // Always show first page
    if ($left > 1) {
        $pages[] = 1;
        if ($left > 2) {
            $pages[] = '...';
        }
    }
    
    // Show range around current page
    for ($i = $left; $i <= $right; $i++) {
        $pages[] = $i;
    }
    
    // Always show last page
    if ($right < $totalPages) {
        if ($right < $totalPages - 1) {
            $pages[] = '...';
        }
        $pages[] = $totalPages;
    }
    
    return $pages;
}

/**
 * Extract keywords from search query
 * 
 * @param string $query Search query
 * @return array Array of keywords
 */
function extractSearchKeywords(string $query): array {
    $query = preg_replace('/\s+/', ' ', trim($query));
    return array_filter(
        explode(' ', strtolower($query)),
        fn($word) => strlen($word) >= 2
    );
}

/**
 * Check if string is valid URL
 * 
 * @param string $url URL to validate
 * @return bool True if valid URL
 */
function isValidUrl(string $url): bool {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Get file extension from path
 * 
 * @param string $path File path
 * @return string File extension
 */
function getFileExtension(string $path): string {
    return strtolower(pathinfo($path, PATHINFO_EXTENSION));
}

/**
 * Convert SearchMode enum to readable string
 * 
 * @param SearchMode $mode Search mode enum
 * @return string Readable mode name
 */
function getSearchModeName(SearchMode $mode): string {
    return match($mode) {
        SearchMode::AND => 'All words (AND)',
        SearchMode::OR => 'Any word (OR)',
        SearchMode::EXACT => 'Exact phrase',
    };
}

/**
 * Create safe redirect URL
 * 
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code
 */
function redirect(string $url, int $statusCode = 302): never {
    header("Location: {$url}", true, $statusCode);
    exit;
}

/**
 * Display JSON response
 * 
 * @param mixed $data Data to encode
 * @param int $statusCode HTTP status code
 */
function jsonResponse(mixed $data, int $statusCode = 200): never {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Log message to file (if debug enabled)
 * 
 * @param string $message Message to log
 * @param string $level Log level
 */
function logMessage(string $message, string $level = 'INFO'): void {
    if (class_exists('EnvLoader') && EnvLoader::getBool('APP_DEBUG', false)) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
        error_log($logEntry);
    }
}
