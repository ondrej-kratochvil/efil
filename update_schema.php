<?php
// update_schema.php - Adds inventory_members table to existing database
require_once __DIR__ . '/config.php';

echo "Updating database schema...\n";

try {
    // Check if table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'inventory_members'");
    if ($stmt->rowCount() > 0) {
        echo "Table 'inventory_members' already exists. Skipping.\n";
        exit;
    }

    // Create inventory_members table
    $sql = "
        CREATE TABLE IF NOT EXISTS inventory_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inventory_id INT NOT NULL,
            user_id INT NOT NULL,
            role ENUM('read', 'write', 'manage') DEFAULT 'read',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY inventory_user_unique (inventory_id, user_id),
            FOREIGN KEY (inventory_id) REFERENCES inventories(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC
    ";

    $pdo->exec($sql);
    echo "Table 'inventory_members' created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
