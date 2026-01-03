<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Get all inventories the user has access to
    $stmt = $db->prepare("
        SELECT i.id, i.name, i.is_demo, im.role, im.is_owner
        FROM inventories i
        INNER JOIN inventory_members im ON i.id = im.inventory_id
        WHERE im.user_id = ?
        ORDER BY im.is_owner DESC, i.name ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $inventories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add admin_efil special handling - they can see all inventories
    $stmtUser = $db->prepare("SELECT system_role FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['system_role'] === 'admin_efil') {
        // Admin can see all inventories
        $stmt = $db->query("SELECT id, name, is_demo FROM inventories ORDER BY name");
        $allInventories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add role 'manage' for admin to all inventories they're not already in
        foreach ($allInventories as $inv) {
            $found = false;
            foreach ($inventories as $userInv) {
                if ($userInv['id'] == $inv['id']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $inventories[] = [
                    'id' => $inv['id'],
                    'name' => $inv['name'],
                    'is_demo' => $inv['is_demo'],
                    'role' => 'manage',
                    'is_owner' => 0
                ];
            }
        }
        
        // Re-sort
        usort($inventories, function($a, $b) {
            if ($a['is_owner'] != $b['is_owner']) {
                return $b['is_owner'] - $a['is_owner'];
            }
            return strcmp($a['name'], $b['name']);
        });
    }
    
    // Mark current inventory
    foreach ($inventories as &$inv) {
        $inv['is_current'] = ($inv['id'] == $_SESSION['inventory_id']);
    }
    
    echo json_encode($inventories);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
