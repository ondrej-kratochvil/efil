<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {

    // Get all inventories the user has access to (including owned)
    // Use UNION to include both owned inventories and member inventories
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        SELECT i.id, i.name, i.is_demo, 'owner' as role, 1 as is_owner
        FROM inventories i
        WHERE i.owner_id = ?
        UNION
        SELECT i.id, i.name, i.is_demo, COALESCE(im.role, 'read') as role, 0 as is_owner
        FROM inventories i
        INNER JOIN inventory_members im ON i.id = im.inventory_id
        WHERE im.user_id = ?
        ORDER BY is_owner DESC, name ASC
    ");
    $stmt->execute([$userId, $userId]);
    $inventories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add admin_efil special handling - they can see all inventories
    $stmtUser = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['role'] === 'admin_efil') {
        // Admin can see all inventories
        $stmt = $pdo->prepare("SELECT id, name, is_demo FROM inventories ORDER BY name");
        $stmt->execute();
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
    $currentInventoryId = $_SESSION['inventory_id'] ?? null;
    foreach ($inventories as &$inv) {
        $inv['is_current'] = ($currentInventoryId !== null && $inv['id'] == $currentInventoryId);
    }
    unset($inv); // Clear reference

    echo json_encode($inventories);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
