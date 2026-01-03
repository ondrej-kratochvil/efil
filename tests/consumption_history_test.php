<?php
/**
 * Test historie čerpání - datum, editace, mazání
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';

echo "=== TEST HISTORIE ČERPÁNÍ ===\n\n";

try {
    $db = getDBConnection();
    
    // 1. Vytvoření testovacího uživatele a evidence
    echo "1. Vytváření testovacího uživatele...\n";
    $testUser = createTestUser($db);
    $testInventory = createTestInventory($db, $testUser['id']);
    
    // 2. Vytvoření testovacího filamentu
    echo "2. Vytváření testovacího filamentu...\n";
    $stmt = $db->prepare("
        INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer, color_name, color_hex, initial_weight_grams, current_weight)
        VALUES (?, 1, 'PLA (STANDARD)', 'Prusament', 'Černá', '#000000', 1000, 1000)
    ");
    $stmt->execute([$testInventory['id']]);
    $filamentId = $db->lastInsertId();
    echo "   Filament vytvořen s ID: $filamentId\n";
    
    // 3. Test přidání čerpání s datem
    echo "\n3. Test přidání čerpání s datem...\n";
    $consumptionDate = '2024-01-15';
    $stmt = $db->prepare("
        INSERT INTO consumption_log (filament_id, consumed_weight, note, consumption_date, created_by)
        VALUES (?, 250, 'Testovací tisk', ?, ?)
    ");
    $stmt->execute([$filamentId, $consumptionDate, $testUser['id']]);
    $consumptionId = $db->lastInsertId();
    
    // Update filament weight
    $stmt = $db->prepare("UPDATE filaments SET current_weight = current_weight - 250 WHERE id = ?");
    $stmt->execute([$filamentId]);
    
    echo "   Čerpání vytvořeno s ID: $consumptionId\n";
    
    // Verify
    $stmt = $db->prepare("SELECT * FROM consumption_log WHERE id = ?");
    $stmt->execute([$consumptionId]);
    $consumption = $stmt->fetch(PDO::FETCH_ASSOC);
    
    assert($consumption['consumed_weight'] == 250, "Hmotnost čerpání nesouhlasí");
    assert($consumption['consumption_date'] == $consumptionDate, "Datum čerpání nesouhlasí");
    assert($consumption['created_by'] == $testUser['id'], "Autor čerpání nesouhlasí");
    echo "   ✓ Čerpání má správné datum a autora\n";
    
    // 4. Test editace čerpání
    echo "\n4. Test editace čerpání...\n";
    $oldWeight = 250;
    $newWeight = 300;
    $newDate = '2024-01-16';
    
    // Adjust filament weight: add back old, subtract new
    $weightDiff = $oldWeight - $newWeight;
    $stmt = $db->prepare("UPDATE filaments SET current_weight = current_weight + ? WHERE id = ?");
    $stmt->execute([$weightDiff, $filamentId]);
    
    // Update consumption
    $stmt = $db->prepare("
        UPDATE consumption_log 
        SET consumed_weight = ?, consumption_date = ?, note = ?
        WHERE id = ?
    ");
    $stmt->execute([$newWeight, $newDate, 'Upravený tisk', $consumptionId]);
    
    // Verify
    $stmt = $db->prepare("SELECT * FROM consumption_log WHERE id = ?");
    $stmt->execute([$consumptionId]);
    $consumption = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT current_weight FROM filaments WHERE id = ?");
    $stmt->execute([$filamentId]);
    $filament = $stmt->fetch(PDO::FETCH_ASSOC);
    
    assert($consumption['consumed_weight'] == $newWeight, "Nová hmotnost čerpání nesouhlasí");
    assert($consumption['consumption_date'] == $newDate, "Nové datum čerpání nesouhlasí");
    assert($filament['current_weight'] == (1000 - $newWeight), "Hmotnost filamentu po editaci nesouhlasí");
    echo "   ✓ Čerpání upraveno a hmotnost filamentu přepočítána\n";
    
    // 5. Test mazání čerpání
    echo "\n5. Test mazání čerpání...\n";
    // Return weight back to filament
    $stmt = $db->prepare("UPDATE filaments SET current_weight = current_weight + ? WHERE id = ?");
    $stmt->execute([$newWeight, $filamentId]);
    
    // Delete consumption
    $stmt = $db->prepare("DELETE FROM consumption_log WHERE id = ?");
    $stmt->execute([$consumptionId]);
    
    // Verify
    $stmt = $db->prepare("SELECT current_weight FROM filaments WHERE id = ?");
    $stmt->execute([$filamentId]);
    $filament = $stmt->fetch(PDO::FETCH_ASSOC);
    
    assert($filament['current_weight'] == 1000, "Hmotnost filamentu po smazání čerpání nesouhlasí");
    echo "   ✓ Čerpání smazáno a hmotnost vrácena\n";
    
    // 6. Test načtení historie
    echo "\n6. Test načtení historie čerpání...\n";
    // Add multiple consumption records
    $dates = ['2024-01-10', '2024-01-15', '2024-01-20'];
    $weights = [100, 150, 200];
    
    foreach ($dates as $idx => $date) {
        $stmt = $db->prepare("
            INSERT INTO consumption_log (filament_id, consumed_weight, consumption_date, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$filamentId, $weights[$idx], $date, $testUser['id']]);
    }
    
    // Fetch history
    $stmt = $db->prepare("
        SELECT * FROM consumption_log 
        WHERE filament_id = ? 
        ORDER BY consumption_date DESC
    ");
    $stmt->execute([$filamentId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    assert(count($history) == 3, "Počet záznamů historie nesouhlasí");
    assert($history[0]['consumption_date'] == '2024-01-20', "Řazení historie je špatné");
    echo "   ✓ Historie načtena a správně seřazena\n";
    
    // Cleanup
    echo "\n7. Úklid testovacích dat...\n";
    cleanupTestData($db, $testUser['id']);
    echo "   ✓ Testovací data odstraněna\n";
    
    echo "\n✅ Všechny testy historie čerpání úspěšně prošly!\n";
    
} catch (Exception $e) {
    echo "\n❌ Test selhal: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
