<?php
// tests/spool_management_test.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';

echo "Running Spool Management Tests...\n";
echo "--------------------------------\n";

// Setup Test Data
$testEmail = 'test_spool_' . time() . '@example.com';
$pdo->exec("DELETE FROM users WHERE email LIKE 'test_spool_%'"); // Cleanup old

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

    // 2. Test: Create spool with all characteristics
    $spoolData = [
        'color' => 'Černá',
        'material' => 'PC',
        'outer_diameter_mm' => 200,
        'width_mm' => 60,
        'weight_grams' => 250,
        'visual_description' => 'S otvory, průměr 200mm'
    ];
    
    // Simulate spools/save.php logic
    $stmt = $pdo->prepare("INSERT INTO spool_library (manufacturer_id, color, material, outer_diameter_mm, width_mm, weight_grams, visual_description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        null, // manufacturer_id can be null
        $spoolData['color'],
        $spoolData['material'],
        $spoolData['outer_diameter_mm'],
        $spoolData['width_mm'],
        $spoolData['weight_grams'],
        $spoolData['visual_description'],
        $userId
    ]);
    $spoolId = $pdo->lastInsertId();
    
    // Verify spool was created with all characteristics
    $stmt = $pdo->prepare("SELECT color, material, outer_diameter_mm, width_mm, weight_grams, visual_description FROM spool_library WHERE id = ?");
    $stmt->execute([$spoolId]);
    $spool = $stmt->fetch();
    
    assertResult("Spool color", $spoolData['color'], $spool['color']);
    assertResult("Spool material", $spoolData['material'], $spool['material']);
    assertResult("Spool diameter", $spoolData['outer_diameter_mm'], (int)$spool['outer_diameter_mm']);
    assertResult("Spool width", $spoolData['width_mm'], (int)$spool['width_mm']);
    assertResult("Spool weight", $spoolData['weight_grams'], (int)$spool['weight_grams']);
    assertResult("Spool description", $spoolData['visual_description'], $spool['visual_description']);

    // 3. Test: Spool can be retrieved with all details (simulate spools/list.php)
    $stmt = $pdo->prepare("
        SELECT s.id, s.color, s.material, s.outer_diameter_mm, s.width_mm, s.weight_grams, s.visual_description, m.name as manufacturer 
        FROM spool_library s
        LEFT JOIN manufacturers m ON s.manufacturer_id = m.id
        WHERE s.id = ?
    ");
    $stmt->execute([$spoolId]);
    $spoolDetails = $stmt->fetch();
    
    if ($spoolDetails && $spoolDetails['color'] === $spoolData['color']) {
        echo "[PASS] Spool details retrieved correctly\n";
    } else {
        echo "[FAIL] Spool details should be retrievable\n";
        exit(1);
    }

    // 4. Test: Create filament with spool
    $stmt = $pdo->prepare("INSERT INTO filaments (inventory_id, user_display_id, material, color_name, color_hex, initial_weight_grams, spool_type_id) VALUES (?, 1, 'PLA', 'Red', '#FF0000', 1000, ?)");
    $stmt->execute([$invId, $spoolId]);
    $filId = $pdo->lastInsertId();
    
    // Verify filament has spool assigned
    $stmt = $pdo->prepare("SELECT spool_type_id FROM filaments WHERE id = ?");
    $stmt->execute([$filId]);
    $filament = $stmt->fetch();
    
    assertResult("Filament spool_id", $spoolId, (int)$filament['spool_type_id']);

    // 5. Test: Weight calculation with spool (netto vs brutto)
    // Netto = initial_weight_grams (1000g)
    // Brutto = netto + spool weight (1000 + 250 = 1250g)
    $stmt = $pdo->prepare("
        SELECT 
            f.initial_weight_grams as netto,
            (f.initial_weight_grams + COALESCE(sl.weight_grams, 0)) as brutto
        FROM filaments f
        LEFT JOIN spool_library sl ON f.spool_type_id = sl.id
        WHERE f.id = ?
    ");
    $stmt->execute([$filId]);
    $weights = $stmt->fetch();
    
    assertResult("Netto weight", 1000, (int)$weights['netto']);
    assertResult("Brutto weight", 1250, (int)$weights['brutto']);

    // 6. Test: Spool with nullable fields
    $stmt = $pdo->prepare("INSERT INTO spool_library (color, material, created_by) VALUES (?, ?, ?)");
    $stmt->execute(['Šedá', 'ABS', $userId]);
    $spoolId2 = $pdo->lastInsertId();
    
    // Verify nullable fields work
    $stmt = $pdo->prepare("SELECT outer_diameter_mm, width_mm, weight_grams FROM spool_library WHERE id = ?");
    $stmt->execute([$spoolId2]);
    $spool2 = $stmt->fetch();
    
    if ($spool2['outer_diameter_mm'] === null && $spool2['width_mm'] === null) {
        echo "[PASS] Spool nullable fields work correctly\n";
    } else {
        echo "[FAIL] Spool nullable fields should be null\n";
        exit(1);
    }

    // Cleanup
    $pdo->exec("DELETE FROM filaments WHERE id = $filId");
    $pdo->exec("DELETE FROM spool_library WHERE id = $spoolId");
    $pdo->exec("DELETE FROM spool_library WHERE id = $spoolId2");
    $pdo->exec("DELETE FROM inventories WHERE id = $invId");
    $pdo->exec("DELETE FROM users WHERE id = $userId");

    echo "\nAll Tests Passed!\n";

} catch (Exception $e) {
    echo "\n[FAIL] Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// assertResult() is now in helpers.php

