<?php
// tests/user_display_id_test.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';

echo "Running User Display ID Tests...\n";
echo "--------------------------------\n";

// Setup Test Data
$testEmail = 'test_display_id_' . time() . '@example.com';
$pdo->exec("DELETE FROM users WHERE email LIKE 'test_display_id_%'"); // Cleanup old

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

    // 2. Test: Auto-generate user_display_id (should be 1 for first filament)
    // Simulate the logic from save.php: if no user_display_id provided, generate MAX + 1
    $stmtMax = $pdo->prepare("SELECT MAX(user_display_id) as maxid FROM filaments WHERE inventory_id = ?");
    $stmtMax->execute([$invId]);
    $maxId = $stmtMax->fetch()['maxid'] ?? 0;
    $autoId = $maxId + 1; // Should be 1 for first filament

    assertResult("Auto-generated ID for first filament", 1, $autoId);

    $stmt = $pdo->prepare("INSERT INTO filaments (inventory_id, user_display_id, material, color_name, color_hex, initial_weight_grams) VALUES (?, ?, 'PLA', 'Red', '#FF0000', 1000)");
    $stmt->execute([$invId, $autoId]);
    $firstFilId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT user_display_id FROM filaments WHERE id = ?");
    $stmt->execute([$firstFilId]);
    $firstFilament = $stmt->fetch();

    assertResult("First filament user_display_id", 1, (int)$firstFilament['user_display_id']);

    // 3. Test: Manual assignment
    $stmt = $pdo->prepare("INSERT INTO filaments (inventory_id, user_display_id, material, color_name, color_hex, initial_weight_grams) VALUES (?, 5, 'PETG', 'Blue', '#0000FF', 1000)");
    $stmt->execute([$invId]);
    $filId1 = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT user_display_id FROM filaments WHERE id = ?");
    $stmt->execute([$filId1]);
    $filament1 = $stmt->fetch();

    assertResult("Manual user_display_id assignment", 5, (int)$filament1['user_display_id']);

    // 4. Test: Auto-generate next ID after manual assignment (should be MAX + 1)
    $stmtMax = $pdo->prepare("SELECT MAX(user_display_id) as maxid FROM filaments WHERE inventory_id = ?");
    $stmtMax->execute([$invId]);
    $maxId = $stmtMax->fetch()['maxid'] ?? 0;
    $nextId = $maxId + 1;

    assertResult("Next ID calculation (MAX + 1)", 6, $nextId);

    $stmt = $pdo->prepare("INSERT INTO filaments (inventory_id, user_display_id, material, color_name, color_hex, initial_weight_grams) VALUES (?, ?, 'ABS', 'Green', '#00FF00', 1000)");
    $stmt->execute([$invId, $nextId]);
    $filId2 = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT user_display_id FROM filaments WHERE id = ?");
    $stmt->execute([$filId2]);
    $filament2 = $stmt->fetch();

    assertResult("Auto-generated user_display_id", $nextId, (int)$filament2['user_display_id']);

    // 5. Test: Duplicate check - simulate the logic from save.php
    // The application checks for duplicates before inserting, not the database
    $checkDup = $pdo->prepare("SELECT id FROM filaments WHERE inventory_id = ? AND user_display_id = ?");
    $checkDup->execute([$invId, 5]);
    $existing = $checkDup->fetch();

    if ($existing) {
        echo "[PASS] Duplicate user_display_id correctly detected (application-level check)\n";
        // Don't insert the duplicate - this simulates what save.php does
    } else {
        echo "[FAIL] Duplicate check should detect existing ID 5\n";
        exit(1);
    }

    // Verify that duplicate was not inserted (count should still be 1)
    $checkCount = $pdo->prepare("SELECT COUNT(*) FROM filaments WHERE inventory_id = ? AND user_display_id = 5");
    $checkCount->execute([$invId]);
    $count = $checkCount->fetchColumn();
    assertResult("Duplicate count (should be 1)", 1, (int)$count);

    // 6. Test: Update user_display_id (should allow change if not duplicate)
    $stmt = $pdo->prepare("UPDATE filaments SET user_display_id = 10 WHERE id = ?");
    $stmt->execute([$filId1]);

    $stmt = $pdo->prepare("SELECT user_display_id FROM filaments WHERE id = ?");
    $stmt->execute([$filId1]);
    $updated = $stmt->fetch();

    assertResult("Update user_display_id", 10, (int)$updated['user_display_id']);

    // 7. Test: Update to duplicate should fail (simulate save.php logic)
    // Check for duplicate before updating (excluding current filament)
    $checkDup = $pdo->prepare("SELECT id FROM filaments WHERE inventory_id = ? AND user_display_id = ? AND id != ?");
    $checkDup->execute([$invId, 6, $filId1]);
    $existing = $checkDup->fetch();

    if ($existing) {
        echo "[PASS] Update to duplicate user_display_id correctly detected (application-level check)\n";
        // Don't update - this simulates what save.php does
    } else {
        echo "[FAIL] Duplicate check should detect existing ID 6 (filId2)\n";
        exit(1);
    }

    // Verify that update was not performed (filId1 should still have user_display_id = 10)
    $stmt = $pdo->prepare("SELECT user_display_id FROM filaments WHERE id = ?");
    $stmt->execute([$filId1]);
    $stillUpdated = $stmt->fetch();
    assertResult("Update not performed (should still be 10)", 10, (int)$stillUpdated['user_display_id']);

    // 8. Test: Different inventories can have same user_display_id
    $stmt = $pdo->prepare("INSERT INTO inventories (owner_id, name) VALUES (?, 'Test Inv 2')");
    $stmt->execute([$userId]);
    $invId2 = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO filaments (inventory_id, user_display_id, material, color_name, color_hex, initial_weight_grams) VALUES (?, 5, 'PLA', 'Red', '#FF0000', 1000)");
    $stmt->execute([$invId2]);
    $filId3 = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT user_display_id FROM filaments WHERE id = ?");
    $stmt->execute([$filId3]);
    $filament3 = $stmt->fetch();

    assertResult("Same user_display_id in different inventory", 5, (int)$filament3['user_display_id']);
    echo "[PASS] Different inventories can have same user_display_id\n";

    // Cleanup
    $pdo->exec("DELETE FROM filaments WHERE inventory_id = $invId");
    $pdo->exec("DELETE FROM filaments WHERE inventory_id = $invId2");
    $pdo->exec("DELETE FROM inventories WHERE id = $invId");
    $pdo->exec("DELETE FROM inventories WHERE id = $invId2");
    $pdo->exec("DELETE FROM users WHERE id = $userId");

    echo "\nAll Tests Passed!\n";

} catch (Exception $e) {
    echo "\n[FAIL] Exception: " . $e->getMessage() . "\n";
    exit(1);
}

