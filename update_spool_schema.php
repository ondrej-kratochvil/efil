<?php
// update_spool_schema.php - Updates spool_library table with new fields
require_once __DIR__ . '/config.php';

echo "Updating spool_library table...\n";

try {
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM spool_library LIKE 'color'");
    if ($stmt->rowCount() > 0) {
        echo "Table 'spool_library' already updated. Skipping.\n";
        exit;
    }

    // Add new columns
    $pdo->exec("ALTER TABLE spool_library
        MODIFY COLUMN manufacturer_id INT NULL,
        MODIFY COLUMN weight_grams INT NULL,
        ADD COLUMN color VARCHAR(50) NULL AFTER weight_grams,
        ADD COLUMN material VARCHAR(50) NULL AFTER color,
        ADD COLUMN outer_diameter_mm INT NULL AFTER material,
        ADD COLUMN width_mm INT NULL AFTER outer_diameter_mm");

    echo "Table 'spool_library' updated successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

