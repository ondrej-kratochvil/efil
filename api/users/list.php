<?php
declare(strict_types=1);

/**
 * List users in current inventory with their roles
 * GET /api/users/list.php
 * 
 * Returns list of users with access to current inventory
 */

session_start();
require_once '../../config.php';
require_once '../helpers/inventory.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nepřihlášen']);
    exit;
}

$userId = $_SESSION['user_id'];
$inventoryId = $_SESSION['inventory_id'] ?? null;

if (!$inventoryId) {
    http_response_code(400);
    echo json_encode(['error' => 'Žádná aktivní evidence']);
    exit;
}

try {
    $inventory = requireInventoryManageAccess($pdo, (int) $inventoryId, (int) $userId);

    // Get all users with access to this inventory
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.email,
            u.role as system_role,
            CASE
                WHEN i.owner_id = u.id THEN 'owner'
                WHEN im.role IS NOT NULL THEN im.role
                ELSE NULL
            END as inventory_role,
            i.owner_id = u.id as is_owner,
            im.created_at as added_at
        FROM users u
        LEFT JOIN inventory_members im ON im.user_id = u.id AND im.inventory_id = ?
        JOIN inventories i ON i.id = ?
        WHERE (im.inventory_id = ? OR i.owner_id = u.id)
        ORDER BY 
            CASE WHEN i.owner_id = u.id THEN 0 ELSE 1 END,
            u.email
    ");
    $stmt->execute([$inventoryId, $inventoryId, $inventoryId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($users);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
