<?php
/**
 * Test verzování výrobců (manufacturers) – helper funkce a nové schéma.
 * Vyžaduje DB po migraci (tabulka manufacturers s manufacturer_id, public, approved, created_at, created_by, invalidated_at, invalidated_by).
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../api/helpers/manufacturers.php';
require_once __DIR__ . '/helpers.php';

echo "=== TEST VERZOVÁNÍ VÝROBCŮ ===\n\n";

$db = getDBConnection();
$testUser = createTestUser($db);
$userId = (int) $testUser['id'];
$inv = createTestInventory($db, $userId);
$invId = (int) $inv['id'];

$testManufacturerId = null;
$testManufacturerName = 'TestVyr_' . time();

try {
    // 1. getNextManufacturerId
    echo "1. getNextManufacturerId()...\n";
    $nextId = getNextManufacturerId($db);
    assert(is_int($nextId) && $nextId >= 1, 'getNextManufacturerId musí vrátit kladné celé číslo');
    echo "   [PASS] next id = $nextId\n";

    // 2. Vložení výrobce (nové schéma)
    echo "\n2. Vložení výrobce (nové schéma)...\n";
    $stmt = $db->prepare("
        INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_at, created_by)
        VALUES (?, ?, 0, 1, NOW(), ?)
    ");
    $stmt->execute([$nextId, $testManufacturerName, $userId]);
    $testManufacturerId = $nextId;
    echo "   [PASS] manufacturer_id = $testManufacturerId\n";

    // 3. getManufacturerName
    echo "\n3. getManufacturerName()...\n";
    $name = getManufacturerName($db, $testManufacturerId, $userId);
    assertResult('getManufacturerName', $testManufacturerName, $name ?? '');
    echo "   [PASS]\n";

    // 4. getManufacturersForOptions
    echo "\n4. getManufacturersForOptions()...\n";
    $options = getManufacturersForOptions($db, $userId, false);
    $found = false;
    foreach ($options as $o) {
        if ((int)$o['id'] === $testManufacturerId && $o['name'] === $testManufacturerName) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "[FAIL] Nový výrobce by měl být v options pro vlastníka\n";
        exit(1);
    }
    echo "   [PASS] výrobce je v options\n";

    // 5. manufacturerNameDuplicateExists
    echo "\n5. manufacturerNameDuplicateExists()...\n";
    $dup = manufacturerNameDuplicateExists($db, $testManufacturerName, $userId, null);
    assertResult('duplicate exists (same name)', true, $dup);
    $dupOther = manufacturerNameDuplicateExists($db, 'NeexistujícíVyr_' . time(), $userId, null);
    assertResult('duplicate not exists (new name)', false, $dupOther);
    $dupExclude = manufacturerNameDuplicateExists($db, $testManufacturerName, $userId, $testManufacturerId);
    assertResult('duplicate excluded (edit self)', false, $dupExclude);
    echo "   [PASS]\n";

    // 6. isManufacturerInUse – nejdřív false
    echo "\n6. isManufacturerInUse() – před použitím...\n";
    $inUse = isManufacturerInUse($db, $testManufacturerId);
    assertResult('in use (before)', false, $inUse);
    echo "   [PASS]\n";

    // 7. Vytvoření filamentu s tímto výrobcem
    echo "\n7. Vytvoření filamentu s výrobcem...\n";
    $stmt = $db->prepare("
        INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer_id, color_name, color_hex, initial_weight_grams)
        VALUES (?, 1, 'PLA', ?, 'Test', '#000000', 100)
    ");
    $stmt->execute([$invId, $testManufacturerId]);
    echo "   [PASS]\n";

    // 8. isManufacturerInUse – nyní true
    echo "\n8. isManufacturerInUse() – po použití...\n";
    $inUse = isManufacturerInUse($db, $testManufacturerId);
    assertResult('in use (after)', true, $inUse);
    echo "   [PASS]\n";

    echo "\n=== VŠECHNY TESTY PROŠLY ===\n";

} catch (Throwable $e) {
    echo "\n[FAIL] " . $e->getMessage() . "\n";
    exit(1);
} finally {
    // Cleanup: filament, výrobce (FK created_by -> users: smazat před uživatelem), pak inventář a uživatel
    if ($invId ?? null) {
        $db->exec("DELETE FROM filaments WHERE inventory_id = $invId");
    }
    if ($testManufacturerId !== null) {
        $db->exec("DELETE FROM manufacturers WHERE manufacturer_id = " . (int) $testManufacturerId);
    }
    cleanupTestData($db, $userId);
}
