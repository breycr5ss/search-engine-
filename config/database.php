<?php
declare(strict_types=1);

require_once __DIR__ . '/env-loader.php';

/**
 * Database connection handler using PDO and PostgreSQL
 * Implements singleton pattern for connection reuse
 */
class Database {
    private static ?PDO $connection = null;
    
    /**
     * Get database connection (singleton)
     * 
     * @return PDO Active database connection
     * @throws RuntimeException if connection fails
     */
    public static function getConnection(): PDO {
        if (self::$connection !== null) {
            return self::$connection;
        }
        
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                EnvLoader::get('DB_HOST', 'localhost'),
                EnvLoader::get('DB_PORT', '5432'),
                EnvLoader::get('DB_NAME', 'search_engine')
            );
            
            self::$connection = new PDO(
                $dsn,
                EnvLoader::get('DB_USER'),
                EnvLoader::get('DB_PASSWORD'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                    PDO::ATTR_PERSISTENT => false,
                ]
            );
            
            // Set PostgreSQL search path
            self::$connection->exec("SET search_path TO public");
            
            // Set timezone if specified
            $timezone = EnvLoader::get('APP_TIMEZONE', 'UTC');
            self::$connection->exec("SET timezone = '{$timezone}'");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            
            if (EnvLoader::getBool('APP_DEBUG', false)) {
                throw new RuntimeException("Unable to connect to database: " . $e->getMessage());
            }
            
            throw new RuntimeException("Unable to connect to database. Please check your configuration.");
        }
        
        return self::$connection;
    }
    
    /**
     * Close database connection
     */
    public static function close(): void {
        self::$connection = null;
    }
    
    /**
     * Test database connection
     * 
     * @return bool True if connection successful, false otherwise
     */
    public static function testConnection(): bool {
        try {
            $conn = self::getConnection();
            $stmt = $conn->query("SELECT version()");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}
