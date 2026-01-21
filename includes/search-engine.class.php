<?php
declare(strict_types=1);

/**
 * Search mode enumeration (PHP 8 Enum)
 */
enum SearchMode: string {
    case AND = 'and';
    case OR = 'or';
    case EXACT = 'exact';
}

/**
 * Main Search Engine Class
 * Handles full-text search with PostgreSQL 17
 */
class SearchEngine {
    private PDO $db;
    private float $executionTime = 0;
    private int $totalResults = 0;
    
    public function __construct(PDO $connection) {
        $this->db = $connection;
    }
    
    /**
     * Perform search query
     * 
     * @param string $query Search query
     * @param int $page Current page number
     * @param int $perPage Results per page
     * @param SearchMode $mode Search mode (AND/OR/EXACT)
     * @return array Search results
     */
    public function search(
        string $query,
        int $page = 1,
        int $perPage = 10,
        SearchMode $mode = SearchMode::AND
    ): array {
        $startTime = microtime(true);
        
        // Sanitize and validate
        $query = $this->sanitizeQuery($query);
        if (empty($query)) {
            return [];
        }
        
        $keywords = $this->extractKeywords($query);
        $offset = ($page - 1) * $perPage;
        
        // Build query based on search mode
        $sql = match($mode) {
            SearchMode::AND => $this->buildAndQuery($keywords),
            SearchMode::OR => $this->buildOrQuery($keywords),
            SearchMode::EXACT => $this->buildExactQuery($query),
        };
        
        // Execute search
        $stmt = $this->db->prepare($sql);
        $this->bindParameters($stmt, $keywords, $query, $mode);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        
        // Get total count for pagination
        $this->totalResults = $this->getTotalCount($query, $keywords, $mode);
        
        $this->executionTime = microtime(true) - $startTime;
        
        return $results;
    }
    
    /**
     * Build AND search query (all keywords must match)
     */
    private function buildAndQuery(array $keywords): string {
        return "
            WITH ranked_results AS (
                SELECT 
                    id, title, description, page_name, 
                    page_fav_icon_path, page_url, created_at,
                    
                    -- Title match scoring (highest priority)
                    (CASE 
                        WHEN " . $this->buildAllKeywordsInTitle($keywords) . " THEN 10.0
                        WHEN " . $this->buildAnyKeywordInTitle($keywords) . " THEN 5.0
                        ELSE 0
                    END) AS title_score,
                    
                    -- Full-text search rank
                    ts_rank(combined_tsv, plainto_tsquery('english', :query_text)) AS text_rank,
                    
                    -- Bonus for keywords in both title and description
                    (CASE 
                        WHEN " . $this->buildBothFieldsMatch($keywords) . " THEN 3.0
                        ELSE 0
                    END) AS match_bonus
                    
                FROM search_items
                WHERE " . $this->buildAndWhere($keywords) . "
            )
            SELECT *,
                   (title_score * 4.0 + text_rank * 2.0 + match_bonus) AS final_score
            FROM ranked_results
            ORDER BY final_score DESC, created_at DESC
            LIMIT :limit OFFSET :offset
        ";
    }
    
    /**
     * Build OR search query (any keyword can match)
     */
    private function buildOrQuery(array $keywords): string {
        return "
            WITH ranked_results AS (
                SELECT 
                    id, title, description, page_name, 
                    page_fav_icon_path, page_url, created_at,
                    
                    -- Full-text search rank
                    ts_rank(combined_tsv, plainto_tsquery('english', :query_text)) AS text_rank,
                    
                    -- Title match bonus
                    (CASE 
                        WHEN " . $this->buildAnyKeywordInTitle($keywords) . " THEN 5.0
                        ELSE 0
                    END) AS title_score
                    
                FROM search_items
                WHERE " . $this->buildOrWhere($keywords) . "
            )
            SELECT *,
                   (title_score * 3.0 + text_rank * 2.0) AS final_score
            FROM ranked_results
            ORDER BY final_score DESC, created_at DESC
            LIMIT :limit OFFSET :offset
        ";
    }
    
    /**
     * Build EXACT phrase search query
     */
    private function buildExactQuery(string $phrase): string {
        return "
            SELECT 
                id, title, description, page_name, 
                page_fav_icon_path, page_url, created_at,
                (CASE 
                    WHEN title ILIKE :phrase_like THEN 15.0
                    WHEN description ILIKE :phrase_like THEN 8.0
                    ELSE ts_rank(combined_tsv, phraseto_tsquery('english', :phrase)) * 5.0
                END) AS final_score
            FROM search_items
            WHERE title ILIKE :phrase_like
               OR description ILIKE :phrase_like
               OR combined_tsv @@ phraseto_tsquery('english', :phrase)
            ORDER BY final_score DESC, created_at DESC
            LIMIT :limit OFFSET :offset
        ";
    }
    
    /**
     * Build WHERE clause for AND search
     */
    private function buildAndWhere(array $keywords): string {
        $conditions = [];
        foreach ($keywords as $i => $keyword) {
            $conditions[] = "(title ILIKE :kw{$i} OR description ILIKE :kw{$i})";
        }
        return implode(' AND ', $conditions);
    }
    
    /**
     * Build WHERE clause for OR search
     */
    private function buildOrWhere(array $keywords): string {
        $conditions = [];
        foreach ($keywords as $i => $keyword) {
            $conditions[] = "(title ILIKE :kw{$i} OR description ILIKE :kw{$i})";
        }
        return implode(' OR ', $conditions);
    }
    
    /**
     * Build condition for all keywords in title
     */
    private function buildAllKeywordsInTitle(array $keywords): string {
        $conditions = [];
        foreach ($keywords as $i => $keyword) {
            $conditions[] = "title ILIKE :kw{$i}";
        }
        return implode(' AND ', $conditions);
    }
    
    /**
     * Build condition for any keyword in title
     */
    private function buildAnyKeywordInTitle(array $keywords): string {
        $conditions = [];
        foreach ($keywords as $i => $keyword) {
            $conditions[] = "title ILIKE :kw{$i}";
        }
        return implode(' OR ', $conditions);
    }
    
    /**
     * Build condition for keywords in both fields
     */
    private function buildBothFieldsMatch(array $keywords): string {
        $conditions = [];
        foreach ($keywords as $i => $keyword) {
            $conditions[] = "(title ILIKE :kw{$i} AND description ILIKE :kw{$i})";
        }
        return implode(' OR ', $conditions);
    }
    
    /**
     * Bind parameters to prepared statement
     */
    private function bindParameters(
        PDOStatement $stmt,
        array $keywords,
        string $query,
        SearchMode $mode
    ): void {
        $stmt->bindValue(':query_text', $query, PDO::PARAM_STR);
        
        if ($mode === SearchMode::EXACT) {
            $stmt->bindValue(':phrase', $query, PDO::PARAM_STR);
            $stmt->bindValue(':phrase_like', "%{$query}%", PDO::PARAM_STR);
        } else {
            foreach ($keywords as $i => $keyword) {
                $stmt->bindValue(":kw{$i}", "%{$keyword}%", PDO::PARAM_STR);
            }
        }
    }
    
    /**
     * Extract keywords from query (filter short words)
     */
    private function extractKeywords(string $query): array {
        // Remove extra spaces and split
        $query = preg_replace('/\s+/', ' ', trim($query));
        return array_filter(
            explode(' ', strtolower($query)),
            fn($word) => strlen($word) >= 2  // Keep words with 2+ characters
        );
    }
    
    /**
     * Sanitize search query
     */
    private function sanitizeQuery(string $query): string {
        $query = trim($query);
        
        // Get max length from environment or use default
        $maxLength = 200;
        if (class_exists('EnvLoader')) {
            $maxLength = EnvLoader::getInt('MAX_QUERY_LENGTH', 200);
        }
        
        return substr($query, 0, $maxLength);
    }
    
    /**
     * Get search execution time
     */
    public function getExecutionTime(): float {
        return $this->executionTime;
    }
    
    /**
     * Get total number of results
     */
    public function getTotalResults(): int {
        return $this->totalResults;
    }
    
    /**
     * Get total count of matching results
     */
    private function getTotalCount(
        string $query,
        array $keywords,
        SearchMode $mode
    ): int {
        $countSql = match($mode) {
            SearchMode::AND => "SELECT COUNT(*) FROM search_items WHERE " . 
                              $this->buildAndWhere($keywords),
            SearchMode::OR => "SELECT COUNT(*) FROM search_items WHERE " . 
                             $this->buildOrWhere($keywords),
            SearchMode::EXACT => "SELECT COUNT(*) FROM search_items WHERE 
                                 title ILIKE :phrase_like 
                                 OR description ILIKE :phrase_like
                                 OR combined_tsv @@ phraseto_tsquery('english', :phrase)",
        };
        
        $stmt = $this->db->prepare($countSql);
        
        if ($mode === SearchMode::EXACT) {
            $stmt->bindValue(':phrase', $query, PDO::PARAM_STR);
            $stmt->bindValue(':phrase_like', "%{$query}%", PDO::PARAM_STR);
        } else {
            foreach ($keywords as $i => $keyword) {
                $stmt->bindValue(":kw{$i}", "%{$keyword}%", PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
