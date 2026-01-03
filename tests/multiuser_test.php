<?php
/**
 * Test multiuser funkcí - sdílení evidencí, role, přepínání
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';

echo "=== TEST MULTIUSER FUNKCÍ ===\n\n";

try {
    $db = getDBConnection();
    
    // 1. Vytvoření testovacích uživatelů
    echo "1. Vytváření testovacích uživatelů...\n";
    $user1 = createTestUser($db, 'test1@efil.local');
    $user2 = createTestUser($db, 'test2@efil.local');
    echo "   Vytvořeni 2 uživatelé\n";
    
    // 2. Vytvoření evidence pro user1
    echo "\n2. Vytváření evidence...\n";
    $inventory = createTestInventory($db, $user1['id'], 'Test Inventory');
    echo "   Evidence vytvořena s ID: {$inventory['id']}\n";
    
    // 3. Test přidání user2 do evidence
    echo "\n3. Test přidání uživatele do evidence...\n";
    $stmt = $db->prepare("
        INSERT INTO inventory_members (inventory_id, user_id, role, is_owner)
        VALUES (?, ?, 'write', FALSE)
    ");
    $stmt->execute([$inventory['id'], $user2['id']]);
    echo "   User2 přidán s rolí 'write'\n";
    
    // Verify
    $stmt = $db->prepare("
        SELECT role, is_owner 
        FROM inventory_members 
        WHERE inventory_id = ? AND user_id = ?
    ");
    $stmt->execute([$inventory['id'], $user2['id']]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    assert($member['role'] == 'write', "Role user2 nesouhlasí");
    assert($member['is_owner'] == 0, "User2 by neměl být owner");
    echo "   ✓ User2 má správnou roli\n";
    
    // 4. Test změny role
    echo "\n4. Test změny role uživatele...\n";
    $stmt = $db->prepare("
        UPDATE inventory_members 
        SET role = 'manage'
        WHERE inventory_id = ? AND user_id = ?
    ");
    $stmt->execute([$inventory['id'], $user2['id']]);
    
    // Verify
    $stmt->execute([$inventory['id'], $user2['id']]);
    $stmt = $db->prepare("
        SELECT role 
        FROM inventory_members 
        WHERE inventory_id = ? AND user_id = ?
    ");
    $stmt->execute([$inventory['id'], $user2['id']]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    assert($member['role'] == 'manage', "Role nebyla změněna");
    echo "   ✓ Role úspěšně změněna na 'manage'\n";
    
    // 5. Test načtení evidencí pro uživatele
    echo "\n5. Test načtení evidencí pro uživatele...\n";
    $stmt = $db->prepare("
        SELECT i.id, i.name, im.role, im.is_owner
        FROM inventories i
        INNER JOIN inventory_members im ON i.id = im.inventory_id
        WHERE im.user_id = ?
    ");
    $stmt->execute([$user2['id']]);
    $inventories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    assert(count($inventories) >= 1, "User2 by měl mít přístup alespoň k jedné evidenci");
    echo "   ✓ User2 má přístup k " . count($inventories) . " evidenci(ím)\n";
    
    // 6. Test odebrání uživatele z evidence
    echo "\n6. Test odebrání uživatele z evidence...\n";
    $stmt = $db->prepare("
        DELETE FROM inventory_members 
        WHERE inventory_id = ? AND user_id = ?
    ");
    $stmt->execute([$inventory['id'], $user2['id']]);
    
    // Verify
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM inventory_members 
        WHERE inventory_id = ? AND user_id = ?
    ");
    $stmt->execute([$inventory['id'], $user2['id']]);
    $count = $stmt->fetchColumn();
    
    assert($count == 0, "User2 nebyl odebrán");
    echo "   ✓ User2 úspěšně odebrán z evidence\n";
    
    // 7. Test více evidencí pro jednoho uživatele
    echo "\n7. Test více evidencí pro jednoho uživatele...\n";
    $inventory2 = createTestInventory($db, $user1['id'], 'Test Inventory 2');
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM inventories i
        INNER JOIN inventory_members im ON i.id = im.inventory_id
        WHERE im.user_id = ?
    ");
    $stmt->execute([$user1['id']]);
    $count = $stmt->fetchColumn();
    
    assert($count >= 2, "User1 by měl mít přístup k alespoň 2 evidencím");
    echo "   ✓ User1 má přístup k více evidencím\n";
    
    // Cleanup
    echo "\n8. Úklid testovacích dat...\n";
    cleanupTestData($db, $user1['id']);
    cleanupTestData($db, $user2['id']);
    echo "   ✓ Testovací data odstraněna\n";
    
    echo "\n✅ Všechny testy multiuser funkcí úspěšně prošly!\n";
    
} catch (Exception $e) {
    echo "\n❌ Test selhal: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
