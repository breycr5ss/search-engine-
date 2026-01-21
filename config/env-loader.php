<?php
declare(strict_types=1);

/**
 * Simple .env file loader for PHP 8
 * Loads environment variables from .env file
 */
class EnvLoader {
    private static ?array $variables = null;
    
    /**
     * Load environment variables from .env file
     * 
     * @param string $path Path to .env file
     * @throws RuntimeException if .env file not found
     */
    public static function load(string $path = __DIR__ . '/../.env'): void {
        if (!file_exists($path)) {
            throw new RuntimeException(".env file not found at: {$path}");
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        self::$variables = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (str_starts_with($line, '#') || empty($line)) {
                continue;
            }
            
            // Parse KEY=VALUE format
            if (preg_match('/^([A-Z_]+)=(.*)$/', $line, $matches)) {
                $key = $matches[1];
                $value = trim($matches[2], '"\'');
                
                self::$variables[$key] = $value;
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
    
    /**
     * Get environment variable value
     * 
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed Variable value or default
     */
    public static function get(string $key, mixed $default = null): mixed {
        return self::$variables[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }
    
    /**
     * Get environment variable as integer
     * 
     * @param string $key Variable name
     * @param int $default Default value if not found
     * @return int Variable value as integer
     */
    public static function getInt(string $key, int $default = 0): int {
        return (int) self::get($key, $default);
    }
    
    /**
     * Get environment variable as boolean
     * 
     * @param string $key Variable name
     * @param bool $default Default value if not found
     * @return bool Variable value as boolean
     */
    public static function getBool(string $key, bool $default = false): bool {
        $value = strtolower((string) self::get($key, ''));
        return in_array($value, ['true', '1', 'yes', 'on'], true) ?: $default;
    }
    
    /**
     * Check if environment variable exists
     * 
     * @param string $key Variable name
     * @return bool True if exists, false otherwise
     */
    public static function has(string $key): bool {
        return isset(self::$variables[$key]) || isset($_ENV[$key]) || getenv($key) !== false;
    }
}
