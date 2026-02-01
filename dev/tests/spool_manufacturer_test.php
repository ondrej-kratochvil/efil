<?php
/**
 * Test vazby M:N mezi spool_types a manufacturers (logická id: spool_type_id, manufacturer_id)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../api/helpers/manufacturers.php';
require_once __DIR__ . '/../../api/helpers/spool_types.php';

echo "=== TEST SPOOL-MANUFACTURER VAZBY ===\n\n";

try {
    $db = getDBConnection();
    
    // 1. Vytvoření testovacího uživatele
    echo "1. Vytváření testovacího uživatele...\n";
    $testUser = createTestUser($db);
    $userId = (int) $testUser['id'];
    
    // 2. Vytvoření výrobců (verzovaná tabulka – logické manufacturer_id)
    echo "\n2. Vytváření testovacích výrobců...\n";
    $manufacturers = ['Prusament', 'Fillamentum', 'Devil Design'];
    $manufIds = [];
    
    foreach ($manufacturers as $name) {
        $nextId = getNextManufacturerId($db);
        $stmt = $db->prepare("INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_at, created_by) VALUES (?, ?, 0, 1, NOW(), ?)");
        $stmt->execute([$nextId, $name, $userId]);
        $manufIds[$name] = $nextId;
    }
    echo "   Vytvořeno " . count($manufIds) . " výrobců\n";
    
    // 3. Vytvoření typu cívky (verzovaná tabulka – logické spool_type_id)
    echo "\n3. Vytváření typu cívky...\n";
    $spoolTypeId = getNextSpoolTypeId($db);
    $stmt = $db->prepare("
        INSERT INTO spool_types (spool_type_id, weight_grams, color, material, outer_diameter_mm, width_mm, public, approved, created_by)
        VALUES (?, 240, 'Černá', 'Plast', 200, 70, 0, 1, ?)
    ");
    $stmt->execute([$spoolTypeId, $userId]);
    echo "   Typ cívky vytvořen s ID: $spoolTypeId\n";
    
    // 4. Test přidání vazeb (spool_id = spool_type_id, manufacturer_id = logické id)
    echo "\n4. Test přidání vazeb na výrobce...\n";
    $selectedManuf = ['Prusament', 'Fillamentum'];
    $stmt = $db->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
    
    foreach ($selectedManuf as $name) {
        $stmt->execute([$spoolTypeId, $manufIds[$name]]);
    }
    echo "   Přidáno " . count($selectedManuf) . " vazeb\n";
    
    // Verify
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM spool_manufacturer 
        WHERE spool_id = ?
    ");
    $stmt->execute([$spoolTypeId]);
    $count = $stmt->fetchColumn();
    
    assert($count == count($selectedManuf), "Počet vazeb nesouhlasí");
    echo "   ✓ Vazby správně vytvořeny\n";
    
    // 5. Test načtení vazeb (JOIN na logická id, aktuální schválená verze výrobce)
    echo "\n5. Test načtení vazeb...\n";
    $stmt = $db->prepare("
        SELECT m.manufacturer_id AS id, m.name
        FROM manufacturers m
        INNER JOIN spool_manufacturer sm ON m.manufacturer_id = sm.manufacturer_id
        WHERE sm.spool_id = ? AND m.approved = 1 AND m.invalidated_at IS NULL
        ORDER BY m.name
    ");
    $stmt->execute([$spoolTypeId]);
    $linkedManuf = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    assert(count($linkedManuf) == count($selectedManuf), "Počet načtených vazeb nesouhlasí");
    assert($linkedManuf[0]['name'] == 'Fillamentum', "První výrobce není správně seřazený");
    echo "   ✓ Vazby správně načteny\n";
    
    // 6. Test aktualizace vazeb (změna výrobců)
    echo "\n6. Test aktualizace vazeb...\n";
    $stmt = $db->prepare("DELETE FROM spool_manufacturer WHERE spool_id = ?");
    $stmt->execute([$spoolTypeId]);
    
    $newManuf = ['Devil Design'];
    $stmt = $db->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
    foreach ($newManuf as $name) {
        $stmt->execute([$spoolTypeId, $manufIds[$name]]);
    }
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM spool_manufacturer 
        WHERE spool_id = ?
    ");
    $stmt->execute([$spoolTypeId]);
    $count = $stmt->fetchColumn();
    
    assert($count == count($newManuf), "Počet vazeb po aktualizaci nesouhlasí");
    echo "   ✓ Vazby úspěšně aktualizovány\n";
    
    // 7. Soft delete typu cívky a smazání vazeb (bez FK CASCADE)
    echo "\n7. Test soft delete a odstranění vazeb...\n";
    $stmt = $db->prepare("DELETE FROM spool_manufacturer WHERE spool_id = ?");
    $stmt->execute([$spoolTypeId]);
    $stmt = $db->prepare("UPDATE spool_types SET invalidated_at = NOW(), invalidated_by = ? WHERE spool_type_id = ?");
    $stmt->execute([$userId, $spoolTypeId]);
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM spool_manufacturer 
        WHERE spool_id = ?
    ");
    $stmt->execute([$spoolTypeId]);
    $count = $stmt->fetchColumn();
    
    assert($count == 0, "Vazby nebyly odstraněny");
    echo "   ✓ Vazby správně odstraněny, typ cívky soft-deleted\n";
    
    // Cleanup
    echo "\n8. Úklid testovacích dat...\n";
    cleanupTestData($db, $testUser['id']);
    echo "   ✓ Testovací data odstraněna\n";
    
    echo "\n✅ Všechny testy spool-manufacturer vazby úspěšně prošly!\n";
    
} catch (Exception $e) {
    echo "\n❌ Test selhal: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
