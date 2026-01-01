<?php
// init_db.php - Sets up the database and seeds it with demo data
require_once __DIR__ . '/config.php';

echo "Initializing database...\n";

// Read schema
$schema = file_get_contents(__DIR__ . '/database/schema.sql');
$queries = explode(';', $schema);

try {
    // 1. Create Tables
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    echo "Tables created successfully.\n";

    // 2. Clear old data (optional, for dev)
    // $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE consumption_log; TRUNCATE TABLE filaments; TRUNCATE TABLE inventories; TRUNCATE TABLE users; SET FOREIGN_KEY_CHECKS = 1;");
    
    // Check if we need to seed
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() > 0) {
        echo "Database already contains users. Skipping seed.\n";
        exit;
    }

    echo "Seeding data...\n";

    // 3. Create Demo User
    $email = 'demo@efil.cz';
    $pass = password_hash('demo1234', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'user')");
    $stmt->execute([$email, $pass]);
    $userId = $pdo->lastInsertId();

    // 4. Create Inventory
    $stmt = $pdo->prepare("INSERT INTO inventories (owner_id, name) VALUES (?, 'Demo Dílna')");
    $stmt->execute([$userId]);
    $invId = $pdo->lastInsertId();

    // 5. Seed Filaments
    $filaments = [
        ['PLA', 'Prusa Polymers', 'Galaxy Black', '#333333', 850, 'Hlavní regál'],
        ['PLA', 'Prusa Polymers', 'Prusa Orange', '#ff8800', 400, 'Hlavní regál'],
        ['PETG', 'Prusa Polymers', 'Urban Grey', '#888888', 950, 'Box 1'],
        ['PETG', 'Devil Design', 'Transparent', '#ffffff', 200, 'Box 1'],
        ['ASA', 'Prusa Polymers', 'Galaxy Black', '#333333', 1000, 'Garáž'],
        ['PLA', 'Gembird', 'Red', '#ff0000', 700, 'Šuplík'],
        ['FLEX', 'Fiberlogy', 'Black', '#000000', 450, 'Box 2'],
    ];

    $stmt = $pdo->prepare("INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer, color_name, color_hex, initial_weight_grams, location, purchase_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    foreach ($filaments as $idx => $f) {
        $stmt->execute([$invId, $idx + 1, $f[0], $f[1], $f[2], $f[3], $f[4], $f[5]]);
    }

    echo "Database initialized with demo data.\n";
    echo "User: $email\nPass: demo1234\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
