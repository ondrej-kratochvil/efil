<?php
// tests/form_persistence_test.php
// This test validates that form values are properly saved and restored
// Note: This is a conceptual test since form persistence is primarily frontend logic
// We test the backend API endpoints that support form persistence

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../api/helpers/manufacturers.php';

echo "Running Form Persistence Tests...\n";
echo "---------------------------------\n";

// Setup Test Data
$testEmail = 'test_form_' . time() . '@example.com';
$pdo->exec("DELETE FROM users WHERE email LIKE 'test_form_%'"); // Cleanup old

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

    // 2. Test: Save filament with all fields (simulates form submission)
    $formData = [
        'mat' => 'PLA',
        'man' => 'TestManufacturer',
        'color' => 'Červená',
        'hex' => '#FF0000',
        'g' => 1000,
        'loc' => 'Regál A',
        'price' => 500,
        'seller' => 'TestSeller',
        'date' => '2024-01-15',
        'spool_id' => null
    ];

    // Ensure manufacturer exists (versioned schema: manufacturer_id, created_by)
    $manLogicalId = null;
    if (!empty($formData['man'])) {
        $opts = getManufacturersForOptions($pdo, (int) $userId);
        foreach ($opts as $o) {
            if ($o['name'] === $formData['man']) {
                $manLogicalId = (int) $o['id'];
                break;
            }
        }
        if ($manLogicalId === null) {
            $manLogicalId = getNextManufacturerId($pdo);
            $stmt = $pdo->prepare("INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_by) VALUES (?, ?, 0, 1, ?)");
            $stmt->execute([$manLogicalId, $formData['man'], $userId]);
        }
    }

    // Create filament (manufacturer_id, not manufacturer)
    $stmt = $pdo->prepare("INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer_id, color_name, color_hex, initial_weight_grams, location, price, seller, purchase_date, spool_type_id) VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $invId,
        $formData['mat'],
        $manLogicalId,
        $formData['color'],
        $formData['hex'],
        $formData['g'],
        $formData['loc'],
        $formData['price'],
        $formData['seller'],
        $formData['date'],
        $formData['spool_id']
    ]);
    $filId = $pdo->lastInsertId();

    // Verify all fields were saved (manufacturer name via helper)
    $stmt = $pdo->prepare("SELECT material, manufacturer_id, color_name, color_hex, initial_weight_grams, location, price, seller, purchase_date FROM filaments WHERE id = ?");
    $stmt->execute([$filId]);
    $filament = $stmt->fetch();
    $savedManName = $filament['manufacturer_id'] !== null ? getManufacturerName($pdo, (int) $filament['manufacturer_id'], $userId) : null;

    assertResult("Material saved", $formData['mat'], $filament['material']);
    assertResult("Manufacturer saved", $formData['man'], $savedManName ?? '');
    assertResult("Color saved", $formData['color'], $filament['color_name']);
    assertResult("Hex saved", $formData['hex'], $filament['color_hex']);
    assertResult("Weight saved", $formData['g'], (int)$filament['initial_weight_grams']);
    assertResult("Location saved", $formData['loc'], $filament['location']);
    assertResult("Price saved", $formData['price'], (int)$filament['price']);
    assertResult("Seller saved", $formData['seller'], $filament['seller']);
    assertResult("Date saved", $formData['date'], $filament['purchase_date']);

    // 3. Test: Update filament (simulates form edit)
    $updatedData = [
        'mat' => 'PETG',
        'man' => 'UpdatedManufacturer',
        'color' => 'Modrá',
        'hex' => '#0000FF',
        'g' => 1500,
        'loc' => 'Regál B'
    ];

    // Ensure updated manufacturer exists (versioned schema)
    $updatedManLogicalId = null;
    if (!empty($updatedData['man'])) {
        $opts = getManufacturersForOptions($pdo, (int) $userId);
        foreach ($opts as $o) {
            if ($o['name'] === $updatedData['man']) {
                $updatedManLogicalId = (int) $o['id'];
                break;
            }
        }
        if ($updatedManLogicalId === null) {
            $updatedManLogicalId = getNextManufacturerId($pdo);
            $stmt = $pdo->prepare("INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_by) VALUES (?, ?, 0, 1, ?)");
            $stmt->execute([$updatedManLogicalId, $updatedData['man'], $userId]);
        }
    }

    // Update filament (manufacturer_id)
    $stmt = $pdo->prepare("UPDATE filaments SET material = ?, manufacturer_id = ?, color_name = ?, color_hex = ?, initial_weight_grams = ?, location = ? WHERE id = ?");
    $stmt->execute([
        $updatedData['mat'],
        $updatedManLogicalId,
        $updatedData['color'],
        $updatedData['hex'],
        $updatedData['g'],
        $updatedData['loc'],
        $filId
    ]);

    // Verify update (manufacturer name via helper)
    $stmt = $pdo->prepare("SELECT material, manufacturer_id, color_name, color_hex, initial_weight_grams, location FROM filaments WHERE id = ?");
    $stmt->execute([$filId]);
    $updated = $stmt->fetch();
    $updatedManName = $updated['manufacturer_id'] !== null ? getManufacturerName($pdo, (int) $updated['manufacturer_id'], $userId) : null;

    assertResult("Material updated", $updatedData['mat'], $updated['material']);
    assertResult("Manufacturer updated", $updatedData['man'], $updatedManName ?? '');
    assertResult("Color updated", $updatedData['color'], $updated['color_name']);
    assertResult("Hex updated", $updatedData['hex'], $updated['color_hex']);
    assertResult("Weight updated", $updatedData['g'], (int)$updated['initial_weight_grams']);
    assertResult("Location updated", $updatedData['loc'], $updated['location']);

    // 4. Test: Options API returns all saved values
    $sql = "SELECT DISTINCT material FROM filaments WHERE inventory_id = ? AND material IS NOT NULL AND material != '' ORDER BY material";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$invId]);
    $materials = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (in_array($updatedData['mat'], $materials)) {
        echo "[PASS] Updated material appears in options\n";
    } else {
        echo "[FAIL] Updated material should appear in options\n";
        exit(1);
    }

    $opts = getManufacturersForOptions($pdo, (int) $userId);
    $manufacturerNames = array_column($opts, 'name');

    if (in_array($updatedData['man'], $manufacturerNames)) {
        echo "[PASS] Updated manufacturer appears in options\n";
    } else {
        echo "[FAIL] Updated manufacturer should appear in options\n";
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

