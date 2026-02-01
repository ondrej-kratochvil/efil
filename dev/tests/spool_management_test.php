<?php
// tests/spool_management_test.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../api/helpers/spool_types.php';

echo "Running Spool Management Tests...\n";
echo "--------------------------------\n";

// Setup Test Data – před mazáním starých testovacích uživatelů přeřadit spool_types (FK created_by)
$testEmail = 'test_spool_' . time() . '@example.com';
$cleanupUserIds = [];
$stmt = $pdo->query("SELECT id FROM users WHERE email LIKE 'test_spool_%'");
while ($row = $stmt->fetch(PDO::FETCH_COLUMN)) {
    $cleanupUserIds[] = (int) $row;
}
if (count($cleanupUserIds) > 0) {
    $other = $pdo->query("SELECT id FROM users WHERE email = 'spool_test_cleanup@example.com' LIMIT 1")->fetchColumn();
    if ($other === false) {
        $stmt = $pdo->query("SELECT id FROM users WHERE email NOT LIKE 'test_spool_%' ORDER BY id LIMIT 1");
        $other = $stmt ? $stmt->fetchColumn() : false;
    }
    if ($other !== false) {
        $other = (int) $other;
        $placeholders = implode(',', array_fill(0, count($cleanupUserIds), '?'));
        $stmt = $pdo->prepare("UPDATE spool_types SET created_by = ? WHERE created_by IN ($placeholders)");
        $stmt->execute(array_merge([$other], $cleanupUserIds));
    }
}
$pdo->exec("DELETE FROM users WHERE email LIKE 'test_spool_%'");

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

    // 2. Test: Create spool with all characteristics (nové schéma spool_types)
    $spoolData = [
        'color' => 'Černá',
        'material' => 'PC',
        'outer_diameter_mm' => 200,
        'width_mm' => 60,
        'weight_grams' => 250,
        'visual_description' => 'S otvory, průměr 200mm'
    ];

    $spoolTypeId = getNextSpoolTypeId($pdo);
    $stmt = $pdo->prepare("
        INSERT INTO spool_types (spool_type_id, weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, public, approved, created_at, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, 1, NOW(), ?)
    ");
    $stmt->execute([
        $spoolTypeId,
        $spoolData['weight_grams'],
        $spoolData['color'],
        $spoolData['material'],
        $spoolData['outer_diameter_mm'],
        $spoolData['width_mm'],
        $spoolData['visual_description'],
        $userId
    ]);

    // Verify spool was created with all characteristics
    $stmt = $pdo->prepare("SELECT color, material, outer_diameter_mm, width_mm, weight_grams, visual_description FROM spool_types WHERE spool_type_id = ? AND approved = 1 AND invalidated_at IS NULL");
    $stmt->execute([$spoolTypeId]);
    $spool = $stmt->fetch();
    
    assertResult("Spool color", $spoolData['color'], $spool['color']);
    assertResult("Spool material", $spoolData['material'], $spool['material']);
    assertResult("Spool diameter", $spoolData['outer_diameter_mm'], (int)$spool['outer_diameter_mm']);
    assertResult("Spool width", $spoolData['width_mm'], (int)$spool['width_mm']);
    assertResult("Spool weight", $spoolData['weight_grams'], (int)$spool['weight_grams']);
    assertResult("Spool description", $spoolData['visual_description'], $spool['visual_description']);

    // 3. Test: Spool can be retrieved with all details (simulate spools/list.php)
    $stmt = $pdo->prepare("
        SELECT st.spool_type_id AS id, st.color, st.material, st.outer_diameter_mm, st.width_mm, st.weight_grams, st.visual_description
        FROM spool_types st
        WHERE st.spool_type_id = ? AND st.approved = 1 AND st.invalidated_at IS NULL
    ");
    $stmt->execute([$spoolTypeId]);
    $spoolDetails = $stmt->fetch();
    
    if ($spoolDetails && $spoolDetails['color'] === $spoolData['color']) {
        echo "[PASS] Spool details retrieved correctly\n";
    } else {
        echo "[FAIL] Spool details should be retrievable\n";
        exit(1);
    }

    // 4. Test: Create filament with spool
    $stmt = $pdo->prepare("INSERT INTO filaments (inventory_id, user_display_id, material, color_name, color_hex, initial_weight_grams, spool_type_id) VALUES (?, 1, 'PLA', 'Red', '#FF0000', 1000, ?)");
    $stmt->execute([$invId, $spoolTypeId]);
    $filId = $pdo->lastInsertId();
    
    // Verify filament has spool assigned
    $stmt = $pdo->prepare("SELECT spool_type_id FROM filaments WHERE id = ?");
    $stmt->execute([$filId]);
    $filament = $stmt->fetch();
    
    assertResult("Filament spool_id", $spoolTypeId, (int)$filament['spool_type_id']);

    // 5. Test: Weight calculation with spool (netto vs brutto)
    // Netto = initial_weight_grams (1000g)
    // Brutto = netto + spool weight (1000 + 250 = 1250g)
    $stmt = $pdo->prepare("
        SELECT 
            f.initial_weight_grams as netto,
            (f.initial_weight_grams + COALESCE(st.weight_grams, 0)) as brutto
        FROM filaments f
        LEFT JOIN spool_types st ON st.spool_type_id = f.spool_type_id AND st.approved = 1 AND st.invalidated_at IS NULL
        WHERE f.id = ?
    ");
    $stmt->execute([$filId]);
    $weights = $stmt->fetch();
    
    assertResult("Netto weight", 1000, (int)$weights['netto']);
    assertResult("Brutto weight", 1250, (int)$weights['brutto']);

    // 6. Test: Spool with nullable fields
    $spoolTypeId2 = getNextSpoolTypeId($pdo);
    $stmt = $pdo->prepare("INSERT INTO spool_types (spool_type_id, color, material, public, approved, created_by) VALUES (?, ?, ?, 0, 1, ?)");
    $stmt->execute([$spoolTypeId2, 'Šedá', 'ABS', $userId]);
    
    // Verify nullable fields work
    $stmt = $pdo->prepare("SELECT outer_diameter_mm, width_mm, weight_grams FROM spool_types WHERE spool_type_id = ? AND approved = 1 AND invalidated_at IS NULL");
    $stmt->execute([$spoolTypeId2]);
    $spool2 = $stmt->fetch();
    
    if ($spool2['outer_diameter_mm'] === null && $spool2['width_mm'] === null) {
        echo "[PASS] Spool nullable fields work correctly\n";
    } else {
        echo "[FAIL] Spool nullable fields should be null\n";
        exit(1);
    }

    // Cleanup: smazat filament, přeřadit spool_types na other (podle spool_type_id i created_by), pak smazat inventář a uživatele
    $userId = (int) $userId;
    $pdo->exec("DELETE FROM filaments WHERE id = $filId");
    $other = $pdo->query("SELECT id FROM users WHERE id != " . $userId . " ORDER BY id LIMIT 1")->fetchColumn();
    if ($other === false) {
        $stmt = $pdo->query("SELECT id FROM users WHERE email = 'spool_test_cleanup@example.com' LIMIT 1");
        $other = $stmt ? $stmt->fetchColumn() : false;
        if ($other === false) {
            $pdo->exec("INSERT INTO users (email, password_hash) VALUES ('spool_test_cleanup@example.com', 'x')");
            $other = $pdo->lastInsertId();
        }
    }
    $other = (int) $other;
    // Naše řádky podle spool_type_id (jistota)
    $stmt = $pdo->prepare("UPDATE spool_types SET created_by = ?, invalidated_at = NOW(), invalidated_by = ? WHERE spool_type_id IN (?, ?)");
    $stmt->execute([$other, $other, $spoolTypeId, $spoolTypeId2]);
    // Všechny zbylé spool_types od našeho uživatele
    $stmt = $pdo->prepare("UPDATE spool_types SET created_by = ? WHERE created_by = ?");
    $stmt->execute([$other, $userId]);
    $pdo->exec("DELETE FROM inventories WHERE id = $invId");
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    echo "\nAll Tests Passed!\n";

} catch (Exception $e) {
    echo "\n[FAIL] Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// assertResult() is now in helpers.php

