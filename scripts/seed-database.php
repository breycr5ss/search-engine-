<?php
declare(strict_types=1);

/**
 * Database Seeding Script
 * Populates search_items table with sample data
 */

// Include required files
require_once __DIR__ . '/../config/env-loader.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../sample-data.php';

// Load environment variables
try {
    EnvLoader::load(__DIR__ . '/../.env');
} catch (RuntimeException $e) {
    echo "Error: .env file not found. Please copy .env.example to .env and configure it.\n";
    exit(1);
}

// Display header
echo "═══════════════════════════════════════════════════\n";
echo "  Search Engine Database Seeder\n";
echo "  PostgreSQL 17 + PHP 8\n";
echo "═══════════════════════════════════════════════════\n\n";

try {
    // Connect to database
    echo "Connecting to database...\n";
    $db = Database::getConnection();
    echo "✓ Connected successfully\n\n";
    
    // Check if $sampleData exists
    if (!isset($sampleData) || !is_array($sampleData)) {
        throw new Exception("Sample data not found or invalid. Check sample-data.php file.");
    }
    
    $totalRecords = count($sampleData);
    $batchSize = 500;
    $inserted = 0;
    
    echo "Total records to insert: " . formatNumber($totalRecords) . "\n";
    echo "Batch size: {$batchSize}\n";
    echo "Database: " . EnvLoader::get('DB_NAME') . "\n\n";
    
    // Ask for confirmation
    echo "This will insert all records into the database.\n";
    echo "Continue? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($confirmation) !== 'yes') {
        echo "\nOperation cancelled.\n";
        exit(0);
    }
    
    echo "\nStarting insertion...\n";
    echo str_repeat("─", 50) . "\n";
    
    $startTime = microtime(true);
    
    // Prepare INSERT statement
    $sql = "
        INSERT INTO search_items 
        (title, description, page_name, page_fav_icon_path, page_url, created_at)
        VALUES 
        (:title, :description, :page_name, :page_fav_icon_path, :page_url, :created_at)
    ";
    
    $stmt = $db->prepare($sql);
    
    // Process in batches
    $batches = array_chunk($sampleData, $batchSize);
    $batchNum = 0;
    $errors = 0;
    
    foreach ($batches as $batch) {
        $batchNum++;
        $db->beginTransaction();
        
        try {
            foreach ($batch as $record) {
                // Validate required fields
                if (empty($record['title']) || empty($record['page_name'])) {
                    $errors++;
                    continue;
                }
                
                $stmt->execute([
                    ':title' => $record['title'],
                    ':description' => $record['description'] ?? null,
                    ':page_name' => $record['page_name'],
                    ':page_fav_icon_path' => $record['page_fav_icon_path'] ?? '/images/favicon/default-favicon.ico',
                    ':page_url' => $record['page_url'] ?? null,
                    ':created_at' => $record['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
                $inserted++;
            }
            
            $db->commit();
            
            $progress = ($inserted / $totalRecords) * 100;
            $bar = str_repeat("█", (int)($progress / 2));
            $spaces = str_repeat("░", 50 - (int)($progress / 2));
            
            echo sprintf(
                "\rBatch %d/%d: [%s%s] %s / %s (%.1f%%)",
                $batchNum,
                count($batches),
                $bar,
                $spaces,
                formatNumber($inserted),
                formatNumber($totalRecords),
                $progress
            );
            
        } catch (Exception $e) {
            $db->rollBack();
            echo "\n\nError in batch {$batchNum}: " . $e->getMessage() . "\n";
            $errors++;
            continue;
        }
    }
    
    echo "\n" . str_repeat("─", 50) . "\n\n";
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    
    // Update statistics
    echo "Updating database statistics...\n";
    $db->exec("ANALYZE search_items");
    echo "✓ Statistics updated\n\n";
    
    // Display summary
    echo "═══════════════════════════════════════════════════\n";
    echo "  SEEDING COMPLETED!\n";
    echo "═══════════════════════════════════════════════════\n";
    echo "✓ Records inserted: " . formatNumber($inserted) . "\n";
    if ($errors > 0) {
        echo "⚠ Errors/Skipped: {$errors}\n";
    }
    echo "⏱ Time taken: " . number_format($duration, 2) . " seconds\n";
    echo "⚡ Records per second: " . formatNumber((int)($inserted / $duration)) . "\n";
    
    // Verify insertion
    $stmt = $db->query("SELECT COUNT(*) FROM search_items");
    $count = $stmt->fetchColumn();
    echo "\n📊 Total records in database: " . formatNumber((int)$count) . "\n";
    
    echo "\nDatabase seeding completed successfully!\n";
    echo "You can now start the PHP server: php -S localhost:8000\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    if (EnvLoader::getBool('APP_DEBUG', false)) {
        echo "\nStack trace:\n";
        echo $e->getTraceAsString() . "\n";
    }
    exit(1);
} finally {
    Database::close();
}
