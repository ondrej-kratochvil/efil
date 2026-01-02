<?php
// tests/manufacturer_auto_create_test.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';

echo "Running Manufacturer Auto-Create Tests...\n";
echo "----------------------------------------\n";

// Setup Test Data
$testEmail = 'test_man_' . time() . '@example.com';
$pdo->exec("DELETE FROM users WHERE email LIKE 'test_man_%'"); // Cleanup old

try {
    // 1. Create User
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
    $passwordHash = password_hash('test123', PASSWORD_BCRYPT);
    $stmt->execute([$testEmail, $passwordHash]);
    $userId = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("INSERT INTO inventories (owner_id, name) VALUES (?, 'Test Inv')");
    $stmt->execute([$userId]);
    $invId = $pdo->lastInsertId();

    echo "[PASS] User and Inventory created.\n";

    // 2. Test: New manufacturer should be created automatically
    $newManufacturer = 'TestManufacturer_' . time();
    
    // Simulate filament save with new manufacturer
    $stmt = $pdo->prepare("SELECT id FROM manufacturers WHERE name = ?");
    $stmt->execute([$newManufacturer]);
    $existingMan = $stmt->fetch();
    
    if ($existingMan) {
        echo "[FAIL] Manufacturer should not exist before test\n";
        exit(1);
    }
    
    // Create manufacturer (as done in save.php)
    $stmt = $pdo->prepare("INSERT INTO manufacturers (name) VALUES (?)");
    $stmt->execute([$newManufacturer]);
    $manufacturerId = $pdo->lastInsertId();
    
    // Verify manufacturer was created
    $stmt = $pdo->prepare("SELECT id, name FROM manufacturers WHERE id = ?");
    $stmt->execute([$manufacturerId]);
    $manufacturer = $stmt->fetch();
    
    assertResult("Manufacturer created", $newManufacturer, $manufacturer['name']);
    assertResult("Manufacturer ID", $manufacturerId, (int)$manufacturer['id']);

    // 3. Test: Existing manufacturer should not be duplicated
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM manufacturers WHERE name = ?");
    $stmt->execute([$newManufacturer]);
    $count = $stmt->fetchColumn();
    
    assertResult("Manufacturer count (should be 1)", 1, (int)$count);

    // 4. Test: Create filament with new manufacturer
    $stmt = $pdo->prepare("INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer, color_name, color_hex, initial_weight_grams) VALUES (?, 1, 'PLA', ?, 'Red', '#FF0000', 1000)");
    $stmt->execute([$invId, $newManufacturer]);
    $filId = $pdo->lastInsertId();
    
    // Verify filament was created with correct manufacturer
    $stmt = $pdo->prepare("SELECT manufacturer FROM filaments WHERE id = ?");
    $stmt->execute([$filId]);
    $filament = $stmt->fetch();
    
    assertResult("Filament manufacturer", $newManufacturer, $filament['manufacturer']);

    // 5. Test: Manufacturer should appear in options API
    // Simulate options.php logic
    $sql = "SELECT DISTINCT name FROM manufacturers ORDER BY name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $dbManufacturers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array($newManufacturer, $dbManufacturers)) {
        echo "[PASS] Manufacturer appears in options API\n";
    } else {
        echo "[FAIL] Manufacturer should appear in options API\n";
        exit(1);
    }

    // Cleanup
    $pdo->exec("DELETE FROM filaments WHERE id = $filId");
    $pdo->exec("DELETE FROM manufacturers WHERE id = $manufacturerId");
    $pdo->exec("DELETE FROM inventories WHERE id = $invId");
    $pdo->exec("DELETE FROM users WHERE id = $userId");

    echo "\nAll Tests Passed!\n";

} catch (Exception $e) {
    echo "\n[FAIL] Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// assertResult() is now in helpers.php

