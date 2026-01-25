<?php
// tests/helpers.php
// Shared helper functions for all tests

if (!function_exists('assertResult')) {
    function assertResult($name, $expected, $actual) {
        // Normalize types for comparison (convert both to same type)
        $normalizedExpected = is_numeric($expected) ? (float)$expected : $expected;
        $normalizedActual = is_numeric($actual) ? (float)$actual : $actual;
        
        // Use == for comparison to handle type coercion, but also check strict if both are same type
        $passed = ($normalizedExpected == $normalizedActual) || ($expected === $actual);
        
        if ($passed) {
            echo "[PASS] $name: Expected " . (is_string($expected) ? "'$expected'" : $expected) . ", Got " . (is_string($actual) ? "'$actual'" : $actual) . "\n";
        } else {
            echo "[FAIL] $name: Expected " . (is_string($expected) ? "'$expected'" : $expected) . ", Got " . (is_string($actual) ? "'$actual'" : $actual) . "\n";
            exit(1);
        }
    }
}

// Get database connection (returns global $pdo from config.php)
if (!function_exists('getDBConnection')) {
    function getDBConnection() {
        global $pdo;
        if (!isset($pdo)) {
            throw new Exception('Database connection ($pdo) not available. Make sure config.php is loaded.');
        }
        return $pdo;
    }
}

// Create a test user
if (!function_exists('createTestUser')) {
    function createTestUser($db, $email = null) {
        if ($email === null) {
            $email = 'test_' . time() . '_' . mt_rand(1000, 9999) . '@test.local';
        }
        
        $passwordHash = password_hash('test123', PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
        $stmt->execute([$email, $passwordHash]);
        $userId = $db->lastInsertId();
        
        return ['id' => $userId, 'email' => $email];
    }
}

// Create a test inventory
if (!function_exists('createTestInventory')) {
    function createTestInventory($db, $userId, $name = 'Test Inventory') {
        $stmt = $db->prepare("INSERT INTO inventories (owner_id, name) VALUES (?, ?)");
        $stmt->execute([$userId, $name]);
        $inventoryId = $db->lastInsertId();
        
        // Also create entry in inventory_members table (owner should have 'manage' role)
        $stmt = $db->prepare("INSERT INTO inventory_members (inventory_id, user_id, role) VALUES (?, ?, 'manage')");
        $stmt->execute([$inventoryId, $userId]);
        
        return ['id' => $inventoryId, 'name' => $name, 'owner_id' => $userId];
    }
}

// Cleanup test data for a user
if (!function_exists('cleanupTestData')) {
    function cleanupTestData($db, $userId) {
        // Delete filaments (which will cascade delete consumption_log)
        $stmt = $db->prepare("DELETE FROM filaments WHERE inventory_id IN (SELECT id FROM inventories WHERE owner_id = ?)");
        $stmt->execute([$userId]);
        
        // Delete inventory_members
        $stmt = $db->prepare("DELETE FROM inventory_members WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Delete inventories
        $stmt = $db->prepare("DELETE FROM inventories WHERE owner_id = ?");
        $stmt->execute([$userId]);
        
        // Delete user
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
    }
}

