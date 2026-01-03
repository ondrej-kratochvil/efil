<?php
/**
 * Test groupování cívek podle výrobce+materiál+barva
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';

echo "=== TEST GROUPOVÁNÍ CÍVEK ===\n\n";

try {
    $db = getDBConnection();
    
    // 1. Vytvoření testovacího uživatele a evidence
    echo "1. Vytváření testovacího uživatele...\n";
    $testUser = createTestUser($db);
    $testInventory = createTestInventory($db, $testUser['id']);
    
    // 2. Vytvoření více filamentů stejného typu
    echo "\n2. Vytváření testovacích filamentů...\n";
    $filaments = [];
    $weights = [500, 300, 200];
    
    foreach ($weights as $idx => $weight) {
        $stmt = $db->prepare("
            INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer, color_name, color_hex, initial_weight_grams, current_weight)
            VALUES (?, ?, 'PLA (STANDARD)', 'Prusament', 'Černá', '#000000', ?, ?)
        ");
        $stmt->execute([$testInventory['id'], $idx + 1, $weight, $weight]);
        $filaments[] = [
            'id' => $db->lastInsertId(),
            'weight' => $weight
        ];
    }
    echo "   Vytvořeno " . count($filaments) . " filamentů\n";
    
    // 3. Test groupování logiky
    echo "\n3. Test groupování logiky...\n";
    $stmt = $db->prepare("
        SELECT id, material, manufacturer, color_name, color_hex, current_weight
        FROM filaments
        WHERE inventory_id = ? AND current_weight > 0
        ORDER BY manufacturer, material, color_name
    ");
    $stmt->execute([$testInventory['id']]);
    $allFilaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by manufacturer + material + color_name
    $groups = [];
    foreach ($allFilaments as $f) {
        $key = $f['manufacturer'] . '|' . $f['material'] . '|' . $f['color_name'];
        if (!isset($groups[$key])) {
            $groups[$key] = [];
        }
        $groups[$key][] = $f;
    }
    
    assert(count($groups) == 1, "Měla by existovat pouze jedna skupina");
    $groupKey = array_key_first($groups);
    $group = $groups[$groupKey];
    
    assert(count($group) == 3, "Skupina by měla obsahovat 3 filamenty");
    echo "   ✓ Filamenty správně seskupeny\n";
    
    // 4. Test výpočtu celkové hmotnosti
    echo "\n4. Test výpočtu celkové hmotnosti skupiny...\n";
    $totalWeight = array_sum(array_column($group, 'current_weight'));
    $expectedWeight = array_sum($weights);
    
    assert($totalWeight == $expectedWeight, "Celková hmotnost skupiny nesouhlasí");
    echo "   ✓ Celková hmotnost: {$totalWeight}g\n";
    
    // 5. Test více skupin
    echo "\n5. Test více skupin (různé barvy)...\n";
    $stmt = $db->prepare("
        INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer, color_name, color_hex, initial_weight_grams, current_weight)
        VALUES (?, 4, 'PLA (STANDARD)', 'Prusament', 'Červená', '#FF0000', 400, 400)
    ");
    $stmt->execute([$testInventory['id']]);
    
    // Re-fetch and re-group
    $stmt = $db->prepare("
        SELECT id, material, manufacturer, color_name, current_weight
        FROM filaments
        WHERE inventory_id = ? AND current_weight > 0
    ");
    $stmt->execute([$testInventory['id']]);
    $allFilaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $groups = [];
    foreach ($allFilaments as $f) {
        $key = $f['manufacturer'] . '|' . $f['material'] . '|' . $f['color_name'];
        if (!isset($groups[$key])) {
            $groups[$key] = [];
        }
        $groups[$key][] = $f;
    }
    
    assert(count($groups) == 2, "Měly by existovat dvě skupiny");
    echo "   ✓ Vytvořeny dvě samostatné skupiny\n";
    
    // 6. Test jednoho filamentu (negroupovaný)
    echo "\n6. Test jednotlivého filamentu (není ve skupině)...\n";
    $stmt = $db->prepare("
        INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer, color_name, color_hex, initial_weight_grams, current_weight)
        VALUES (?, 5, 'PETG', 'Prusament', 'Modrá', '#0000FF', 1000, 1000)
    ");
    $stmt->execute([$testInventory['id']]);
    
    // Re-fetch and re-group
    $stmt->execute([$testInventory['id']]);
    $allFilaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $groups = [];
    foreach ($allFilaments as $f) {
        $key = $f['manufacturer'] . '|' . $f['material'] . '|' . $f['color_name'];
        if (!isset($groups[$key])) {
            $groups[$key] = [];
        }
        $groups[$key][] = $f;
    }
    
    assert(count($groups) == 3, "Měly by existovat tři skupiny");
    
    // Find single-item group
    $singleGroups = array_filter($groups, fn($g) => count($g) === 1);
    assert(count($singleGroups) >= 1, "Měla by existovat alespoň jedna skupina s jedním item");
    echo "   ✓ Jednotlivý filament není seskupen\n";
    
    // Cleanup
    echo "\n7. Úklid testovacích dat...\n";
    cleanupTestData($db, $testUser['id']);
    echo "   ✓ Testovací data odstraněna\n";
    
    echo "\n✅ Všechny testy groupování úspěšně prošly!\n";
    
} catch (Exception $e) {
    echo "\n❌ Test selhal: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
