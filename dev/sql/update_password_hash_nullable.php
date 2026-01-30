<?php
declare(strict_types=1);

/**
 * Povolí NULL v users.password_hash pro účty čekající na nastavení hesla.
 * Spusťte jednou na existující databázi.
 */

require_once __DIR__ . '/../../config.php';

echo "Updating users.password_hash to allow NULL...\n";

try {
    $pdo->exec("ALTER TABLE users MODIFY password_hash VARCHAR(255) NULL COMMENT 'NULL = účet čeká na nastavení hesla'");
    echo "✓ users.password_hash is now nullable.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
