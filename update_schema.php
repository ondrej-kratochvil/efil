<?php
require_once __DIR__ . '/../../config.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inventory_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inventory_id INT NOT NULL,
            user_id INT NOT NULL,
            role ENUM('read', 'write', 'manage') DEFAULT 'read',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (inventory_id) REFERENCES inventories(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_membership (inventory_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table inventory_members created.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
