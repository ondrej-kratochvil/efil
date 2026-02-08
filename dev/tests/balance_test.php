<?php
// tests/balance_test.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../api/helpers/manufacturers.php';
require_once __DIR__ . '/../../api/helpers/spool_types.php';

echo "Running Balance Calculation Tests...\n";
echo "------------------------------------\n";

// Setup Test Data – před mazáním starých testovacích uživatelů přeřadit spool_types/manufacturers (FK created_by)
$testEmail = 'test_calc_' . time() . '@example.com';
$cleanupUserIds = [];
$stmt = $pdo->query("SELECT id FROM users WHERE email LIKE 'test_calc_%'");
while ($row = $stmt->fetch(PDO::FETCH_COLUMN)) {
    $cleanupUserIds[] = (int) $row;
}
if (count($cleanupUserIds) > 0) {
    $other = $pdo->query("SELECT id FROM users WHERE email = 'balance_test_cleanup@example.com' LIMIT 1")->fetchColumn();
    if ($other === false) {
        $stmt = $pdo->query("SELECT id FROM users WHERE email NOT LIKE 'test_calc_%' ORDER BY id LIMIT 1");
        $other = $stmt ? $stmt->fetchColumn() : false;
    }
    if ($other !== false) {
        $other = (int) $other;
        $placeholders = implode(',', array_fill(0, count($cleanupUserIds), '?'));
        $stmt = $pdo->prepare("UPDATE spool_types SET created_by = ? WHERE created_by IN ($placeholders)");
        $stmt->execute(array_merge([$other], $cleanupUserIds));
        $stmt = $pdo->prepare("UPDATE manufacturers SET created_by = ? WHERE created_by IN ($placeholders)");
        $stmt->execute(array_merge([$other], $cleanupUserIds));
    }
}
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

    $spoolTypeId = getNextSpoolTypeId($pdo);
    $stmt = $pdo->prepare("INSERT INTO spool_types (spool_type_id, weight_grams, public, approved, created_by) VALUES (?, 200, 0, 1, ?)");
    $stmt->execute([$spoolTypeId, $userId]);
    $stmt = $pdo->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
    $stmt->execute([$spoolTypeId, $manLogicalId]);

    // Assign spool to filament
    $stmt = $pdo->prepare("UPDATE filaments SET spool_type_id = ? WHERE id = ?");
    $stmt->execute([$spoolTypeId, $filId]);

    // Current Netto is 850g. Tare is 200g. Brutto should be 1050g.
    $brutto = getBrutto($pdo, $filId);
    assertResult("Brutto Weight (Netto 850 + Tare 200)", 1050, $brutto);

    // Cleanup: přeřadit naše řádky podle spool_type_id / created_by, pak smazat uživatele (FK RESTRICT)
    $userId = (int) $userId;
    $stmt = $pdo->prepare("DELETE FROM spool_manufacturer WHERE spool_id = ?");
    $stmt->execute([$spoolTypeId]);
    $other = $pdo->query("SELECT id FROM users WHERE id != " . $userId . " ORDER BY id LIMIT 1")->fetchColumn();
    if ($other === false) {
        $stmt = $pdo->query("SELECT id FROM users WHERE email = 'balance_test_cleanup@example.com' LIMIT 1");
        $other = $stmt ? $stmt->fetchColumn() : false;
        if ($other === false) {
            $pdo->exec("INSERT INTO users (email, password_hash) VALUES ('balance_test_cleanup@example.com', 'x')");
            $other = $pdo->lastInsertId();
        }
    }
    $other = (int) $other;
    // Přesně náš řádek: podle spool_type_id (náš záznam)
    $stmt = $pdo->prepare("UPDATE spool_types SET created_by = ?, invalidated_at = NOW(), invalidated_by = ? WHERE spool_type_id = ?");
    $stmt->execute([$other, $other, $spoolTypeId]);
    $stmt = $pdo->prepare("UPDATE manufacturers SET created_by = ? WHERE created_by = ?");
    $stmt->execute([$other, $userId]);
    // Všechny zbylé spool_types od našeho uživatele (pro jistotu)
    $stmt = $pdo->prepare("UPDATE spool_types SET created_by = ? WHERE created_by = ?");
    $stmt->execute([$other, $userId]);
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

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
            (f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0) + COALESCE(st.weight_grams, 0)) as brutto
        FROM filaments f
        LEFT JOIN consumption_log cl ON f.id = cl.filament_id
        LEFT JOIN spool_types st ON st.spool_type_id = f.spool_type_id AND st.approved = 1 AND st.invalidated_at IS NULL
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
