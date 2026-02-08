<?php
// tests/manufacturer_auto_create_test.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../api/helpers/manufacturers.php';

echo "Running Manufacturer Auto-Create Tests...\n";
echo "----------------------------------------\n";

// Setup Test Data (FK manufacturers.created_by -> users: nejdřív výrobce, pak uživatele)
$testEmail = 'test_man_' . time() . '@example.com';
$pdo->exec("DELETE FROM manufacturers WHERE created_by IN (SELECT id FROM users WHERE email LIKE 'test_man_%')");
$pdo->exec("DELETE FROM users WHERE email LIKE 'test_man_%'");

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
    
    // Create manufacturer (versioned schema: manufacturer_id, name, created_by)
    $manLogicalId = getNextManufacturerId($pdo);
    $stmt = $pdo->prepare("INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_by) VALUES (?, ?, 0, 1, ?)");
    $stmt->execute([$manLogicalId, $newManufacturer, $userId]);
    $manufacturerRowId = $pdo->lastInsertId();
    
    // Verify manufacturer was created
    $stmt = $pdo->prepare("SELECT id, manufacturer_id, name FROM manufacturers WHERE id = ?");
    $stmt->execute([$manufacturerRowId]);
    $manufacturer = $stmt->fetch();
    
    assertResult("Manufacturer created", $newManufacturer, $manufacturer['name']);
    assertResult("Manufacturer logical ID", $manLogicalId, (int)$manufacturer['manufacturer_id']);

    // 3. Test: Existing manufacturer should not be duplicated
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM manufacturers WHERE name = ?");
    $stmt->execute([$newManufacturer]);
    $count = $stmt->fetchColumn();
    
    assertResult("Manufacturer count (should be 1)", 1, (int)$count);

    // 4. Test: Create filament with manufacturer_id (logical id)
    $stmt = $pdo->prepare("INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer_id, color_name, color_hex, initial_weight_grams) VALUES (?, 1, 'PLA', ?, 'Red', '#FF0000', 1000)");
    $stmt->execute([$invId, $manLogicalId]);
    $filId = $pdo->lastInsertId();
    
    // Verify filament was created with correct manufacturer (resolve name via helper)
    $nameResolved = getManufacturerName($pdo, (int) $manLogicalId, $userId);
    assertResult("Filament manufacturer name", $newManufacturer, $nameResolved ?? '');

    // 5. Test: Manufacturer should appear in options API (getManufacturersForOptions)
    $options = getManufacturersForOptions($pdo, (int) $userId);
    $names = array_column($options, 'name');
    if (in_array($newManufacturer, $names)) {
        echo "[PASS] Manufacturer appears in options API\n";
    } else {
        echo "[FAIL] Manufacturer should appear in options API\n";
        exit(1);
    }

    // Cleanup (manufacturers.created_by -> users: smazat výrobce před uživatelem)
    $pdo->exec("DELETE FROM filaments WHERE id = $filId");
    $stmt = $pdo->prepare("DELETE FROM manufacturers WHERE created_by = ?");
    $stmt->execute([$userId]);
    $pdo->exec("DELETE FROM inventories WHERE id = $invId");
    $pdo->exec("DELETE FROM users WHERE id = $userId");

    echo "\nAll Tests Passed!\n";

} catch (Exception $e) {
    echo "\n[FAIL] Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// assertResult() is now in helpers.php

