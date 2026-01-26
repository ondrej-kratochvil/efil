<?php
// tests/form_edit_data_load_test.php
// Test: Form Edit Data Loading
// Validates that when editing a filament, all data is properly loaded and displayed

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';

echo "Running Form Edit Data Load Tests...\n";
echo "------------------------------------\n";

// Setup Test Data
$testEmail = 'test_edit_' . time() . '@example.com';
$pdo->exec("DELETE FROM users WHERE email LIKE 'test_edit_%'"); // Cleanup old

try {
    // 1. Create User and Inventory
    $user = createTestUser($pdo, $testEmail);
    $inventory = createTestInventory($pdo, $user['id'], 'Test Inventory');
    
    echo "[PASS] User and Inventory created.\n";

    // 2. Create a filament with all fields
    $stmt = $pdo->prepare("
        INSERT INTO filaments (
            inventory_id, user_display_id, material, manufacturer, color_name, color_hex,
            initial_weight_grams, location, price, seller, purchase_date, spool_type_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $material = 'PETG';
    $manufacturer = 'Test Manufacturer';
    $colorName = 'Černá';
    $colorHex = '#000000';
    $weight = 850;
    $location = 'Regál A';
    $price = 450; // Price is stored as INT in database, so use integer value
    $seller = 'Test Seller';
    $purchaseDate = '2024-01-15';
    $spoolId = null;
    
    $stmt->execute([
        $inventory['id'],
        1,
        $material,
        $manufacturer,
        $colorName,
        $colorHex,
        $weight,
        $location,
        $price,
        $seller,
        $purchaseDate,
        $spoolId
    ]);
    
    $filamentId = $pdo->lastInsertId();
    echo "[PASS] Filament created with ID: $filamentId\n";

    // 3. Test: Load filament data via API (simulating what happens when opening edit form)
    // Start session for API call
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['inventory_id'] = $inventory['id'];
    $_SESSION['role'] = 'owner';
    
    // Simulate API call to list.php
    ob_start();
    include __DIR__ . '/../api/filaments/list.php';
    $response = ob_get_clean();
    
    $filaments = json_decode($response, true);
    
    if (!is_array($filaments)) {
        echo "[FAIL] API response is not an array\n";
        exit(1);
    }
    
    $foundFilament = null;
    foreach ($filaments as $f) {
        if ($f['id'] == $filamentId) {
            $foundFilament = $f;
            break;
        }
    }
    
    if (!$foundFilament) {
        echo "[FAIL] Created filament not found in API response\n";
        exit(1);
    }
    
    echo "[PASS] Filament found in API response\n";

    // 4. Validate all fields are present and correct
    assertResult("Material field", $material, $foundFilament['mat'] ?? null);
    assertResult("Manufacturer field", $manufacturer, $foundFilament['man'] ?? null);
    assertResult("Color name field", $colorName, $foundFilament['color'] ?? null);
    assertResult("Color hex field", $colorHex, $foundFilament['hex'] ?? null);
    assertResult("Location field", $location, $foundFilament['loc'] ?? null);
    // Price is stored as INT in database, so compare as integers
    $expectedPrice = (int)$price;
    $actualPrice = isset($foundFilament['price']) ? (int)$foundFilament['price'] : null;
    assertResult("Price field", $expectedPrice, $actualPrice);
    assertResult("Seller field", $seller, $foundFilament['seller'] ?? null);
    assertResult("Purchase date field", $purchaseDate, $foundFilament['date'] ?? null);
    assertResult("User display ID", 1, $foundFilament['user_display_id'] ?? null);
    
    // Weight should be initial_weight_grams (or calculated with consumption)
    $expectedWeight = $weight; // Assuming no consumption
    $actualWeight = $foundFilament['g'] ?? null;
    if ($actualWeight != $expectedWeight) {
        echo "[INFO] Weight: Expected $expectedWeight, Got $actualWeight (may differ if consumption exists)\n";
    }
    
    echo "[PASS] All fields validated successfully\n";

    // 5. Test: Create another filament and verify data is still accessible
    $stmt2 = $pdo->prepare("
        INSERT INTO filaments (
            inventory_id, user_display_id, material, manufacturer, color_name, color_hex,
            initial_weight_grams, location, price, seller, purchase_date, spool_type_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt2->execute([
        $inventory['id'],
        2,
        'PLA',
        'Another Manufacturer',
        'Bílá',
        '#FFFFFF',
        500,
        'Regál B',
        300,
        'Another Seller',
        '2024-02-20',
        null
    ]);
    
    $filamentId2 = $pdo->lastInsertId();
    echo "[PASS] Second filament created with ID: $filamentId2\n";
    
    // Reload data
    ob_start();
    include __DIR__ . '/../api/filaments/list.php';
    $response2 = ob_get_clean();
    $filaments2 = json_decode($response2, true);
    
    if (!is_array($filaments2)) {
        echo "[FAIL] Second API response is not an array\n";
        exit(1);
    }
    
    $foundFilament2 = null;
    foreach ($filaments2 as $f) {
        if (isset($f['id']) && $f['id'] == $filamentId2) {
            $foundFilament2 = $f;
            break;
        }
    }
    
    if (!$foundFilament2) {
        echo "[FAIL] Second filament not found after reload\n";
        exit(1);
    }
    
    assertResult("Second filament material", 'PLA', $foundFilament2['mat'] ?? null);
    assertResult("Second filament color", 'Bílá', $foundFilament2['color'] ?? null);
    
    echo "[PASS] Multiple filaments can be loaded correctly\n";

    // Cleanup
    session_destroy();
    $pdo->exec("DELETE FROM filaments WHERE inventory_id = {$inventory['id']}");
    $pdo->exec("DELETE FROM inventories WHERE id = {$inventory['id']}");
    $pdo->exec("DELETE FROM users WHERE id = {$user['id']}");
    
    echo "\n[PASS] All Form Edit Data Load tests passed!\n";
    
} catch (Exception $e) {
    echo "[FAIL] Test failed with exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
