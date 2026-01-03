<?php
/**
 * Migration script to update spool_library schema:
 * 1. Create spool_manufacturer junction table (M:N)
 * 2. Migrate data from manufacturer_id to spool_manufacturer
 * 3. Drop manufacturer_id column from spool_library
 */

require_once __DIR__ . '/config.php';

try {
    $db = getDBConnection();
    
    echo "Starting spool-manufacturer schema migration...\n";
    
    // 1. Create spool_manufacturer table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS spool_manufacturer (
            id INT AUTO_INCREMENT PRIMARY KEY,
            spool_id INT NOT NULL,
            manufacturer_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY spool_manufacturer_unique (spool_id, manufacturer_id),
            FOREIGN KEY (spool_id) REFERENCES spool_library(id) ON DELETE CASCADE,
            FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
    ");
    echo "✓ spool_manufacturer table created or already exists\n";
    
    // 2. Check if manufacturer_id column exists in spool_library
    $stmt = $db->query("SHOW COLUMNS FROM spool_library LIKE 'manufacturer_id'");
    $hasManufacturerId = $stmt->fetch() !== false;
    
    if ($hasManufacturerId) {
        echo "Found manufacturer_id column, migrating data...\n";
        
        // Migrate existing data
        $stmt = $db->query("SELECT id, manufacturer_id FROM spool_library WHERE manufacturer_id IS NOT NULL");
        $spools = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $migratedCount = 0;
        foreach ($spools as $spool) {
            try {
                $insertStmt = $db->prepare("
                    INSERT IGNORE INTO spool_manufacturer (spool_id, manufacturer_id)
                    VALUES (?, ?)
                ");
                $insertStmt->execute([$spool['id'], $spool['manufacturer_id']]);
                $migratedCount++;
            } catch (Exception $e) {
                echo "Warning: Could not migrate spool {$spool['id']}: {$e->getMessage()}\n";
            }
        }
        
        echo "✓ Migrated $migratedCount spool-manufacturer relationships\n";
        
        // 3. Drop manufacturer_id column
        $db->exec("ALTER TABLE spool_library DROP FOREIGN KEY IF EXISTS spool_library_ibfk_1");
        $db->exec("ALTER TABLE spool_library DROP COLUMN manufacturer_id");
        echo "✓ manufacturer_id column removed from spool_library\n";
    } else {
        echo "manufacturer_id column not found, skipping migration\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
