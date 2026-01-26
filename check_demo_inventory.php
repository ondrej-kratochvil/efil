<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

echo "Checking demo inventory...\n\n";

try {
    // Find demo user
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = 'demo@efil.cz'");
    $stmt->execute();
    $demoUser = $stmt->fetch();
    
    if (!$demoUser) {
        echo "❌ Demo user not found.\n";
        exit;
    }
    
    echo "✓ Demo user found: {$demoUser['email']} (ID: {$demoUser['id']})\n\n";
    
    // Find demo inventory
    $stmt = $pdo->prepare("SELECT id, name, owner_id, is_demo FROM inventories WHERE owner_id = ?");
    $stmt->execute([$demoUser['id']]);
    $inventory = $stmt->fetch();
    
    if (!$inventory) {
        echo "❌ Demo inventory not found.\n";
        exit;
    }
    
    $isDemoValue = $inventory['is_demo'];
    $isDemoBool = (bool)$isDemoValue;
    
    echo "Inventory: {$inventory['name']} (ID: {$inventory['id']})\n";
    echo "Owner ID: {$inventory['owner_id']}\n";
    echo "is_demo (raw): " . var_export($isDemoValue, true) . "\n";
    echo "is_demo (bool): " . ($isDemoBool ? 'true' : 'false') . "\n";
    echo "is_demo (int): " . (int)$isDemoValue . "\n\n";
    
    if ($isDemoBool) {
        echo "✅ Demo inventory is correctly marked as demo (is_demo = 1)\n";
    } else {
        echo "❌ Demo inventory is NOT marked as demo (is_demo = 0 or NULL)\n";
        echo "   Run fix_demo_inventory.php to fix this.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
