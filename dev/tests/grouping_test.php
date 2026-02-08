<?php
/**
 * Test groupování cívek podle výrobce+materiál+barva
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../api/helpers/manufacturers.php';

echo "=== TEST GROUPOVÁNÍ CÍVEK ===\n\n";

try {
    $db = getDBConnection();
    
    // 1. Vytvoření testovacího uživatele a evidence
    echo "1. Vytváření testovacího uživatele...\n";
    $testUser = createTestUser($db);
    $userId = (int) $testUser['id'];
    $testInventory = createTestInventory($db, $testUser['id']);
    
    // 2. Vytvoření výrobce (versioned schema) a filamentů
    echo "\n2. Vytváření testovacích filamentů...\n";
    $manLogicalId = getNextManufacturerId($db);
    $stmtM = $db->prepare("INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_by) VALUES (?, 'Prusament', 0, 1, ?)");
    $stmtM->execute([$manLogicalId, $userId]);
    $filaments = [];
    $weights = [500, 300, 200];
    
    foreach ($weights as $idx => $weight) {
        $stmt = $db->prepare("
            INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer_id, color_name, color_hex, initial_weight_grams)
            VALUES (?, ?, 'PLA (STANDARD)', ?, 'Černá', '#000000', ?)
        ");
        $stmt->execute([$testInventory['id'], $idx + 1, $manLogicalId, $weight]);
        $filaments[] = [
            'id' => $db->lastInsertId(),
            'weight' => $weight
        ];
    }
    echo "   Vytvořeno " . count($filaments) . " filamentů\n";

    $listFilamentsWithWeight = function ($invId) use ($db) {
        $stmt = $db->prepare("
            SELECT f.id, f.material, COALESCE(m.name, '') AS manufacturer, f.color_name, f.color_hex,
                   (f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0)) as g
            FROM filaments f
            LEFT JOIN consumption_log cl ON cl.filament_id = f.id
            LEFT JOIN manufacturers m ON m.manufacturer_id = f.manufacturer_id AND m.approved = 1 AND m.invalidated_at IS NULL
            WHERE f.inventory_id = ?
            GROUP BY f.id, f.material, m.name, f.color_name, f.color_hex, f.initial_weight_grams
            HAVING g > 0
            ORDER BY manufacturer, f.material, f.color_name
        ");
        $stmt->execute([$invId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    };
    
    // 3. Test groupování logiky
    echo "\n3. Test groupování logiky...\n";
    $allFilaments = $listFilamentsWithWeight($testInventory['id']);
    
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
    $totalWeight = (int) array_sum(array_column($group, 'g'));
    $expectedWeight = array_sum($weights);
    
    assert($totalWeight == $expectedWeight, "Celková hmotnost skupiny nesouhlasí");
    echo "   ✓ Celková hmotnost: {$totalWeight}g\n";
    
    // 5. Test více skupin
    echo "\n5. Test více skupin (různé barvy)...\n";
    $stmt = $db->prepare("
        INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer_id, color_name, color_hex, initial_weight_grams)
        VALUES (?, 4, 'PLA (STANDARD)', ?, 'Červená', '#FF0000', 400)
    ");
    $stmt->execute([$testInventory['id'], $manLogicalId]);
    
    $stmt = $db->prepare("
        SELECT f.id, f.material, COALESCE(m.name, '') AS manufacturer, f.color_name,
               (f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0)) as g
        FROM filaments f
        LEFT JOIN consumption_log cl ON cl.filament_id = f.id
        LEFT JOIN manufacturers m ON m.manufacturer_id = f.manufacturer_id AND m.approved = 1 AND m.invalidated_at IS NULL
        WHERE f.inventory_id = ?
        GROUP BY f.id, f.material, m.name, f.color_name, f.initial_weight_grams
        HAVING g > 0
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
        INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer_id, color_name, color_hex, initial_weight_grams)
        VALUES (?, 5, 'PETG', ?, 'Modrá', '#0000FF', 1000)
    ");
    $stmt->execute([$testInventory['id'], $manLogicalId]);
    
    $allFilaments = $listFilamentsWithWeight($testInventory['id']);
    
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
    
    // Cleanup (manufacturers.created_by -> users: smazat výrobce před uživatelem)
    echo "\n7. Úklid testovacích dat...\n";
    $stmt = $db->prepare("DELETE FROM manufacturers WHERE created_by = ?");
    $stmt->execute([$userId]);
    cleanupTestData($db, $testUser['id']);
    echo "   ✓ Testovací data odstraněna\n";
    
    echo "\n✅ Všechny testy groupování úspěšně prošly!\n";
    
} catch (Exception $e) {
    echo "\n❌ Test selhal: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
