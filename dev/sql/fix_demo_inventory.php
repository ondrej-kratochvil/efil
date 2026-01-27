<?php
declare(strict_types=1);

/**
 * fix_demo_inventory.php - Fixes existing demo inventory to set is_demo = 1
 * 
 * This script updates the demo inventory (owned by demo@efil.cz) to have is_demo = 1
 * Run this once if you have an existing database where demo inventory was created without is_demo flag
 */

require_once __DIR__ . '/../../config.php';

echo "Fixing demo inventory...\n";

try {
    // Find demo user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'demo@efil.cz'");
    $stmt->execute();
    $demoUser = $stmt->fetch();
    
    if (!$demoUser) {
        echo "Demo user not found. Nothing to fix.\n";
        exit;
    }
    
    $demoUserId = $demoUser['id'];
    
    // Find demo inventory (owned by demo user)
    $stmt = $pdo->prepare("SELECT id, name, is_demo FROM inventories WHERE owner_id = ?");
    $stmt->execute([$demoUserId]);
    $inventory = $stmt->fetch();
    
    if (!$inventory) {
        echo "Demo inventory not found. Nothing to fix.\n";
        exit;
    }
    
    echo "Found inventory: {$inventory['name']} (ID: {$inventory['id']}, is_demo: " . ($inventory['is_demo'] ? '1' : '0') . ")\n";
    
    if ($inventory['is_demo']) {
        echo "Demo inventory already has is_demo = 1. Nothing to fix.\n";
        exit;
    }
    
    // Update demo inventory to set is_demo = 1
    $stmt = $pdo->prepare("UPDATE inventories SET is_demo = 1 WHERE id = ?");
    $stmt->execute([$inventory['id']]);
    
    echo "✓ Demo inventory updated: is_demo = 1\n";
    echo "Demo account restrictions should now work correctly.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
