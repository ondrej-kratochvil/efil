<?php
/**
 * Migration Script: Update consumption_log table
 * 
 * Adds:
 * - consumption_date (DATE) - date when consumption occurred
 * - created_by (INT FK to users) - user who created the consumption record
 * 
 * Run this script only once on existing databases.
 * For new installations, use init_db.php instead.
 */

require_once 'config.php';

echo "🔄 Starting consumption_log table migration...\n\n";

try {
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM consumption_log LIKE 'consumption_date'");
    $dateExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM consumption_log LIKE 'created_by'");
    $createdByExists = $stmt->rowCount() > 0;
    
    if ($dateExists && $createdByExists) {
        echo "✅ Columns already exist. No migration needed.\n";
        exit(0);
    }
    
    // Add consumption_date column
    if (!$dateExists) {
        echo "📝 Adding consumption_date column...\n";
        $pdo->exec("ALTER TABLE consumption_log ADD COLUMN consumption_date DATE NOT NULL DEFAULT (CURRENT_DATE) AFTER description");
        
        // Set consumption_date from created_at for existing records
        $pdo->exec("UPDATE consumption_log SET consumption_date = DATE(created_at) WHERE consumption_date IS NULL OR consumption_date = '0000-00-00'");
        
        echo "✅ consumption_date column added successfully.\n";
    }
    
    // Add created_by column
    if (!$createdByExists) {
        echo "📝 Adding created_by column...\n";
        $pdo->exec("ALTER TABLE consumption_log ADD COLUMN created_by INT AFTER consumption_date");
        $pdo->exec("ALTER TABLE consumption_log ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
        
        echo "✅ created_by column added successfully.\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
