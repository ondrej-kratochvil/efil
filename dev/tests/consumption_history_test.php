<?php
/**
 * Test historie čerpání - datum, editace, mazání
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../api/helpers/manufacturers.php';

echo "=== TEST HISTORIE ČERPÁNÍ ===\n\n";

try {
    $db = getDBConnection();
    
    // 1. Vytvoření testovacího uživatele a evidence
    echo "1. Vytváření testovacího uživatele...\n";
    $testUser = createTestUser($db);
    $userId = (int) $testUser['id'];
    $testInventory = createTestInventory($db, $testUser['id']);
    
    // 2. Vytvoření výrobce (versioned schema) a testovacího filamentu
    echo "2. Vytváření testovacího filamentu...\n";
    $manLogicalId = getNextManufacturerId($db);
    $stmtM = $db->prepare("INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_by) VALUES (?, 'Prusament', 0, 1, ?)");
    $stmtM->execute([$manLogicalId, $userId]);
    $stmt = $db->prepare("
        INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer_id, color_name, color_hex, initial_weight_grams)
        VALUES (?, 1, 'PLA (STANDARD)', ?, 'Černá', '#000000', 1000)
    ");
    $stmt->execute([$testInventory['id'], $manLogicalId]);
    $filamentId = $db->lastInsertId();
    echo "   Filament vytvořen s ID: $filamentId\n";

    $getCurrentWeight = function ($filamentId) use ($db) {
        $stmt = $db->prepare("
            SELECT f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0) as g
            FROM filaments f
            LEFT JOIN consumption_log cl ON cl.filament_id = f.id
            WHERE f.id = ?
            GROUP BY f.id
        ");
        $stmt->execute([$filamentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['g'] : 0;
    };
    
    // 3. Test přidání čerpání s datem
    echo "\n3. Test přidání čerpání s datem...\n";
    $consumptionDate = '2024-01-15';
    $stmt = $db->prepare("
        INSERT INTO consumption_log (filament_id, amount_grams, description, consumption_date, created_by)
        VALUES (?, -250, 'Testovací tisk', ?, ?)
    ");
    $stmt->execute([$filamentId, $consumptionDate, $testUser['id']]);
    $consumptionId = $db->lastInsertId();
    echo "   Čerpání vytvořeno s ID: $consumptionId\n";
    
    // Verify
    $stmt = $db->prepare("SELECT id, filament_id, amount_grams, description, consumption_date, created_by, created_at FROM consumption_log WHERE id = ?");
    $stmt->execute([$consumptionId]);
    $consumption = $stmt->fetch(PDO::FETCH_ASSOC);
    
    assert(abs((int)$consumption['amount_grams']) == 250, "Hmotnost čerpání nesouhlasí");
    assert($consumption['consumption_date'] == $consumptionDate, "Datum čerpání nesouhlasí");
    assert($consumption['created_by'] == $testUser['id'], "Autor čerpání nesouhlasí");
    echo "   ✓ Čerpání má správné datum a autora\n";
    
    // 4. Test editace čerpání
    echo "\n4. Test editace čerpání...\n";
    $newWeight = 300;
    $newDate = '2024-01-16';
    
    $stmt = $db->prepare("
        UPDATE consumption_log 
        SET amount_grams = ?, consumption_date = ?, description = ?
        WHERE id = ?
    ");
    $stmt->execute([-$newWeight, $newDate, 'Upravený tisk', $consumptionId]);
    
    $stmt = $db->prepare("SELECT * FROM consumption_log WHERE id = ?");
    $stmt->execute([$consumptionId]);
    $consumption = $stmt->fetch(PDO::FETCH_ASSOC);
    
    assert(abs((int)$consumption['amount_grams']) == $newWeight, "Nová hmotnost čerpání nesouhlasí");
    assert($consumption['consumption_date'] == $newDate, "Nové datum čerpání nesouhlasí");
    assert($getCurrentWeight($filamentId) === (1000 - $newWeight), "Hmotnost filamentu po editaci nesouhlasí");
    echo "   ✓ Čerpání upraveno a hmotnost filamentu přepočítána\n";
    
    // 5. Test mazání čerpání
    echo "\n5. Test mazání čerpání...\n";
    $stmt = $db->prepare("DELETE FROM consumption_log WHERE id = ?");
    $stmt->execute([$consumptionId]);
    assert($getCurrentWeight($filamentId) === 1000, "Hmotnost filamentu po smazání čerpání nesouhlasí");
    echo "   ✓ Čerpání smazáno a hmotnost vrácena\n";
    
    // 6. Test načtení historie
    echo "\n6. Test načtení historie čerpání...\n";
    // Add multiple consumption records
    $dates = ['2024-01-10', '2024-01-15', '2024-01-20'];
    $weights = [100, 150, 200];
    
    foreach ($dates as $idx => $date) {
        $stmt = $db->prepare("
            INSERT INTO consumption_log (filament_id, amount_grams, consumption_date, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$filamentId, -$weights[$idx], $date, $testUser['id']]);
    }
    
    // Fetch history
    $stmt = $db->prepare("
        SELECT id, filament_id, amount_grams, description, consumption_date, created_by, created_at
        FROM consumption_log
        WHERE filament_id = ?
        ORDER BY consumption_date DESC
    ");
    $stmt->execute([$filamentId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    assert(count($history) == 3, "Počet záznamů historie nesouhlasí");
    assert($history[0]['consumption_date'] == '2024-01-20', "Řazení historie je špatné");
    echo "   ✓ Historie načtena a správně seřazena\n";
    
    // Cleanup (manufacturers.created_by -> users: smazat výrobce před uživatelem)
    echo "\n7. Úklid testovacích dat...\n";
    $stmt = $db->prepare("DELETE FROM manufacturers WHERE created_by = ?");
    $stmt->execute([$userId]);
    cleanupTestData($db, $testUser['id']);
    echo "   ✓ Testovací data odstraněna\n";
    
    echo "\n✅ Všechny testy historie čerpání úspěšně prošly!\n";
    
} catch (Exception $e) {
    echo "\n❌ Test selhal: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
