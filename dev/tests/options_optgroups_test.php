<?php
// tests/options_optgroups_test.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../api/helpers/manufacturers.php';

echo "Running Options Optgroups Tests...\n";
echo "----------------------------------\n";

// Setup Test Data
$testEmail = 'test_opt_' . time() . '@example.com';
$pdo->exec("DELETE FROM users WHERE email LIKE 'test_opt_%'"); // Cleanup old

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

    // 2. Create multiple filaments with different materials to test frequency
    $materials = ['PLA', 'PETG', 'PLA', 'ABS', 'PLA', 'PETG', 'PLA']; // PLA appears 4x, PETG 2x, ABS 1x
    $filamentIds = [];
    
    foreach ($materials as $index => $material) {
        $stmt = $pdo->prepare("INSERT INTO filaments (inventory_id, user_display_id, material, color_name, color_hex, initial_weight_grams) VALUES (?, ?, ?, 'Test', '#000', 1000)");
        $stmt->execute([$invId, $index + 1, $material]);
        $filamentIds[] = $pdo->lastInsertId();
    }

    echo "[PASS] Test filaments created.\n";

    // 3. Test: Get material frequencies (simulate options.php logic)
    $sql = "SELECT material, COUNT(*) as count FROM filaments WHERE inventory_id = ? AND material IS NOT NULL AND material != '' GROUP BY material ORDER BY count DESC, material ASC LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$invId]);
    $topMaterials = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // PLA should be first (4 occurrences), PETG second (2 occurrences), ABS third (1 occurrence)
    assertResult("Top material (should be PLA)", 'PLA', $topMaterials[0]);
    assertResult("Second material (should be PETG)", 'PETG', $topMaterials[1]);
    assertResult("Third material (should be ABS)", 'ABS', $topMaterials[2]);
    assertResult("Top materials count", 3, count($topMaterials));

    // 4. Test: Get all materials and split into top/others
    $sql = "SELECT DISTINCT material FROM filaments WHERE inventory_id = ? AND material IS NOT NULL AND material != '' ORDER BY material";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$invId]);
    $dbMaterials = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Default materials list (simplified for test)
    $defaultMaterials = ['PLA (Standard)', 'PETG', 'ABS'];
    $allMaterials = array_values(array_unique(array_merge($dbMaterials, $defaultMaterials)));
    sort($allMaterials);
    
    // Split into top and others
    $othersMaterials = array_values(array_diff($allMaterials, $topMaterials));
    sort($othersMaterials);
    
    // Verify structure
    if (is_array($topMaterials) && count($topMaterials) > 0) {
        echo "[PASS] Top materials structure is correct\n";
    } else {
        echo "[FAIL] Top materials should not be empty\n";
        exit(1);
    }
    
    if (is_array($othersMaterials)) {
        echo "[PASS] Others materials structure is correct\n";
    } else {
        echo "[FAIL] Others materials should be an array\n";
        exit(1);
    }

    // 5. Test: Manufacturers with frequencies (versioned schema: manufacturer_id, created_by)
    $manufacturerNames = ['ManufacturerA', 'ManufacturerB', 'ManufacturerA', 'ManufacturerA'];
    $nameToLogicalId = [];
    foreach (array_unique($manufacturerNames) as $name) {
        $manLogicalId = getNextManufacturerId($pdo);
        $stmt = $pdo->prepare("INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_by) VALUES (?, ?, 0, 1, ?)");
        $stmt->execute([$manLogicalId, $name, $userId]);
        $nameToLogicalId[$name] = $manLogicalId;
    }
    foreach ($manufacturerNames as $index => $name) {
        $stmt = $pdo->prepare("UPDATE filaments SET manufacturer_id = ? WHERE id = ?");
        $stmt->execute([$nameToLogicalId[$name], $filamentIds[$index]]);
    }
    // Get manufacturer frequencies (join manufacturers for name)
    $sql = "SELECT m.name, COUNT(*) as cnt FROM filaments f JOIN manufacturers m ON m.manufacturer_id = f.manufacturer_id AND m.approved = 1 AND m.invalidated_at IS NULL WHERE f.inventory_id = ? AND f.manufacturer_id IS NOT NULL GROUP BY m.manufacturer_id, m.name ORDER BY cnt DESC, m.name ASC LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$invId]);
    $topManufacturers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    assertResult("Top manufacturer (should be ManufacturerA)", 'ManufacturerA', $topManufacturers[0]);
    assertResult("Second manufacturer (should be ManufacturerB)", 'ManufacturerB', $topManufacturers[1]);

    // 6. Test: Empty inventory should return plain array (no optgroups)
    $stmt = $pdo->prepare("INSERT INTO inventories (owner_id, name) VALUES (?, 'Empty Inv')");
    $stmt->execute([$userId]);
    $emptyInvId = $pdo->lastInsertId();
    
    $sql = "SELECT material, COUNT(*) as count FROM filaments WHERE inventory_id = ? AND material IS NOT NULL AND material != '' GROUP BY material ORDER BY count DESC, material ASC LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$emptyInvId]);
    $emptyTopMaterials = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($emptyTopMaterials)) {
        echo "[PASS] Empty inventory returns empty top materials\n";
    } else {
        echo "[FAIL] Empty inventory should return empty top materials\n";
        exit(1);
    }

    // Cleanup (manufacturers.created_by -> users: smazat výrobce před uživatelem)
    foreach ($filamentIds as $filId) {
        $pdo->exec("DELETE FROM filaments WHERE id = $filId");
    }
    $pdo->exec("DELETE FROM inventories WHERE id = $invId");
    $pdo->exec("DELETE FROM inventories WHERE id = $emptyInvId");
    foreach ($nameToLogicalId as $logicalId) {
        $pdo->exec("DELETE FROM manufacturers WHERE manufacturer_id = " . (int) $logicalId);
    }
    $pdo->exec("DELETE FROM users WHERE id = $userId");

    echo "\nAll Tests Passed!\n";

} catch (Exception $e) {
    echo "\n[FAIL] Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// assertResult() is now in helpers.php

