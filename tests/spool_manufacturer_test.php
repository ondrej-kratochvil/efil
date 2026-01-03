<?php
/**
 * Test vazby M:N mezi spool_library a manufacturers
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';

echo "=== TEST SPOOL-MANUFACTURER VAZBY ===\n\n";

try {
    $db = getDBConnection();
    
    // 1. Vytvoření testovacího uživatele
    echo "1. Vytváření testovacího uživatele...\n";
    $testUser = createTestUser($db);
    
    // 2. Vytvoření výrobců
    echo "\n2. Vytváření testovacích výrobců...\n";
    $manufacturers = ['Prusament', 'Fillamentum', 'Devil Design'];
    $manufIds = [];
    
    foreach ($manufacturers as $name) {
        $stmt = $db->prepare("INSERT IGNORE INTO manufacturers (name) VALUES (?)");
        $stmt->execute([$name]);
        
        $stmt = $db->prepare("SELECT id FROM manufacturers WHERE name = ?");
        $stmt->execute([$name]);
        $manufIds[$name] = $stmt->fetchColumn();
    }
    echo "   Vytvořeno " . count($manufIds) . " výrobců\n";
    
    // 3. Vytvoření typu cívky
    echo "\n3. Vytváření typu cívky...\n";
    $stmt = $db->prepare("
        INSERT INTO spool_library (weight_grams, color, material, outer_diameter_mm, width_mm, created_by)
        VALUES (240, 'Černá', 'Plast', 200, 70, ?)
    ");
    $stmt->execute([$testUser['id']]);
    $spoolId = $db->lastInsertId();
    echo "   Typ cívky vytvořen s ID: $spoolId\n";
    
    // 4. Test přidání vazeb
    echo "\n4. Test přidání vazeb na výrobce...\n";
    $selectedManuf = ['Prusament', 'Fillamentum'];
    $stmt = $db->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
    
    foreach ($selectedManuf as $name) {
        $stmt->execute([$spoolId, $manufIds[$name]]);
    }
    echo "   Přidáno " . count($selectedManuf) . " vazeb\n";
    
    // Verify
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM spool_manufacturer 
        WHERE spool_id = ?
    ");
    $stmt->execute([$spoolId]);
    $count = $stmt->fetchColumn();
    
    assert($count == count($selectedManuf), "Počet vazeb nesouhlasí");
    echo "   ✓ Vazby správně vytvořeny\n";
    
    // 5. Test načtení vazeb
    echo "\n5. Test načtení vazeb...\n";
    $stmt = $db->prepare("
        SELECT m.id, m.name
        FROM manufacturers m
        INNER JOIN spool_manufacturer sm ON m.id = sm.manufacturer_id
        WHERE sm.spool_id = ?
        ORDER BY m.name
    ");
    $stmt->execute([$spoolId]);
    $linkedManuf = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    assert(count($linkedManuf) == count($selectedManuf), "Počet načtených vazeb nesouhlasí");
    assert($linkedManuf[0]['name'] == 'Fillamentum', "První výrobce není správně seřazený");
    echo "   ✓ Vazby správně načteny\n";
    
    // 6. Test aktualizace vazeb (změna výrobců)
    echo "\n6. Test aktualizace vazeb...\n";
    // Delete existing
    $stmt = $db->prepare("DELETE FROM spool_manufacturer WHERE spool_id = ?");
    $stmt->execute([$spoolId]);
    
    // Add new
    $newManuf = ['Devil Design'];
    $stmt = $db->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
    foreach ($newManuf as $name) {
        $stmt->execute([$spoolId, $manufIds[$name]]);
    }
    
    // Verify
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM spool_manufacturer 
        WHERE spool_id = ?
    ");
    $stmt->execute([$spoolId]);
    $count = $stmt->fetchColumn();
    
    assert($count == count($newManuf), "Počet vazeb po aktualizaci nesouhlasí");
    echo "   ✓ Vazby úspěšně aktualizovány\n";
    
    // 7. Test cascade delete
    echo "\n7. Test cascade delete...\n";
    $stmt = $db->prepare("DELETE FROM spool_library WHERE id = ?");
    $stmt->execute([$spoolId]);
    
    // Verify vazby byly také smazány
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM spool_manufacturer 
        WHERE spool_id = ?
    ");
    $stmt->execute([$spoolId]);
    $count = $stmt->fetchColumn();
    
    assert($count == 0, "Vazby nebyly cascade smazány");
    echo "   ✓ Vazby správně cascade smazány\n";
    
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
