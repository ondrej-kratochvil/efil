<?php
// tests/balance_test.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../api/helpers/manufacturers.php';

echo "Running Balance Calculation Tests...\n";
echo "------------------------------------\n";

// Setup Test Data (FK manufacturers.created_by -> users: nejdřív výrobce, pak uživatele)
$testEmail = 'test_calc_' . time() . '@example.com';
$pdo->exec("DELETE FROM manufacturers WHERE created_by IN (SELECT id FROM users WHERE email LIKE 'test_calc_%')");
$pdo->exec("DELETE FROM users WHERE email LIKE 'test_calc_%'");

try {
    // 1. Create User
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (?, 'hash')");
    $stmt->execute([$testEmail]);
    $userId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO inventories (owner_id, name) VALUES (?, 'Test Inv')");
    $stmt->execute([$userId]);
    $invId = $pdo->lastInsertId();

    echo "[PASS] User and Inventory created.\n";

    // 2. Create Filament (1000g Initial)
    $stmt = $pdo->prepare("INSERT INTO filaments (inventory_id, user_display_id, material, color_name, color_hex, initial_weight_grams) VALUES (?, 1, 'PLA', 'Test', '#000', 1000)");
    $stmt->execute([$invId]);
    $filId = $pdo->lastInsertId();

    // 3. Test Initial Balance
    $balance = getBalance($pdo, $filId);
    assertResult("Initial Balance", 1000, $balance);

    // 4. Consumption (-200g)
    logConsumption($pdo, $filId, -200);
    $balance = getBalance($pdo, $filId);
    assertResult("After Consumption (-200g)", 800, $balance);

    // 5. Correction (+50g)
    logConsumption($pdo, $filId, 50);
    $balance = getBalance($pdo, $filId);
    assertResult("After Correction (+50g)", 850, $balance);

    // 6. Test Brutto Calculation with Spool
    // Add manufacturer (versioned schema) and spool (200g)
    $manLogicalId = getNextManufacturerId($pdo);
    $stmt = $pdo->prepare("INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_by) VALUES (?, 'TestMan', 0, 1, ?)");
    $stmt->execute([$manLogicalId, $userId]);
    $manRowId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO spool_library (weight_grams) VALUES (200)");
    $stmt->execute();
    $spoolId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
    $stmt->execute([$spoolId, $manLogicalId]);

    // Assign spool to filament
    $stmt = $pdo->prepare("UPDATE filaments SET spool_type_id = ? WHERE id = ?");
    $stmt->execute([$spoolId, $filId]);

    // Current Netto is 850g. Tare is 200g. Brutto should be 1050g.
    $brutto = getBrutto($pdo, $filId);
    assertResult("Brutto Weight (Netto 850 + Tare 200)", 1050, $brutto);

    // Cleanup (manufacturers.created_by -> users.id: smazat výrobce před uživatelem)
    $pdo->exec("DELETE FROM spool_manufacturer WHERE spool_id = $spoolId");
    $pdo->exec("DELETE FROM spool_library WHERE id = $spoolId");
    $stmt = $pdo->prepare("DELETE FROM manufacturers WHERE created_by = ?");
    $stmt->execute([$userId]);
    $pdo->exec("DELETE FROM users WHERE id = $userId"); // Cascade: inventory & filaments

    echo "\nAll Tests Passed!\n";

} catch (Exception $e) {
    echo "\n[FAIL] Exception: " . $e->getMessage() . "\n";
}

// Helpers
function getBalance($pdo, $fid) {
    $stmt = $pdo->prepare("
        SELECT (f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0)) as g
        FROM filaments f
        LEFT JOIN consumption_log cl ON f.id = cl.filament_id
        WHERE f.id = ?
        GROUP BY f.id
    ");
    $stmt->execute([$fid]);
    return (int)$stmt->fetchColumn();
}

function getBrutto($pdo, $fid) {
    $stmt = $pdo->prepare("
        SELECT
            (f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0) + COALESCE(sl.weight_grams, 0)) as brutto
        FROM filaments f
        LEFT JOIN consumption_log cl ON f.id = cl.filament_id
        LEFT JOIN spool_library sl ON f.spool_type_id = sl.id
        WHERE f.id = ?
        GROUP BY f.id
    ");
    $stmt->execute([$fid]);
    return (int)$stmt->fetchColumn();
}

function logConsumption($pdo, $fid, $amount) {
    $stmt = $pdo->prepare("INSERT INTO consumption_log (filament_id, amount_grams) VALUES (?, ?)");
    $stmt->execute([$fid, $amount]);
}

// assertResult() is now in helpers.php
