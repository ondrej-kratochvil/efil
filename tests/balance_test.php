<?php
// tests/balance_test.php
require_once __DIR__ . '/../config.php';

echo "Running Balance Calculation Tests...\n";
echo "------------------------------------\n";

// Setup Test Data
$testEmail = 'test_calc_' . time() . '@example.com';
$pdo->exec("DELETE FROM users WHERE email LIKE 'test_calc_%'"); // Cleanup old

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
    // Add Spool (200g)
    $stmt = $pdo->prepare("INSERT INTO manufacturers (name) VALUES ('TestMan')");
    $stmt->execute();
    $manId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO spool_library (manufacturer_id, weight_grams) VALUES (?, 200)");
    $stmt->execute([$manId]);
    $spoolId = $pdo->lastInsertId();

    // Assign spool to filament
    $stmt = $pdo->prepare("UPDATE filaments SET spool_type_id = ? WHERE id = ?");
    $stmt->execute([$spoolId, $filId]);

    // Current Netto is 850g. Tare is 200g. Brutto should be 1050g.
    $brutto = getBrutto($pdo, $filId);
    assertResult("Brutto Weight (Netto 850 + Tare 200)", 1050, $brutto);

    // Cleanup
    $pdo->exec("DELETE FROM users WHERE id = $userId"); // Cascade deletes inventory & filaments
    $pdo->exec("DELETE FROM spool_library WHERE id = $spoolId");
    $pdo->exec("DELETE FROM manufacturers WHERE id = $manId");

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

function assertResult($name, $expected, $actual) {
    if ($expected === $actual) {
        echo "[PASS] $name: Expected $expected, Got $actual\n";
    } else {
        echo "[FAIL] $name: Expected $expected, Got $actual\n";
        exit(1);
    }
}
