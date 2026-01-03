<?php
/**
 * List users in current inventory with their roles
 * GET /api/users/list.php
 * 
 * Returns list of users with access to current inventory
 */

session_start();
require_once '../../config.php';

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
    // Check if user has manage permission
    $stmt = $pdo->prepare("
        SELECT role FROM inventory_members 
        WHERE inventory_id = ? AND user_id = ?
    ");
    $stmt->execute([$inventoryId, $userId]);
    $member = $stmt->fetch();
    
    // Check if user is owner
    $stmt = $pdo->prepare("SELECT owner_id FROM inventories WHERE id = ?");
    $stmt->execute([$inventoryId]);
    $inventory = $stmt->fetch();
    $isOwner = ($inventory && $inventory['owner_id'] == $userId);
    
    // Check if user is admin_efil
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    $isAdmin = ($user && $user['role'] === 'admin_efil');
    
    // Only owner, manage role, or admin can view users
    if (!$isOwner && !$isAdmin && (!$member || $member['role'] !== 'manage')) {
        http_response_code(403);
        echo json_encode(['error' => 'Nedostatečná oprávnění']);
        exit;
    }
    
    // Get all users with access to this inventory
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.email,
            u.role as system_role,
            CASE 
                WHEN i.owner_id = u.id THEN 'owner'
                ELSE im.role
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
    $users = $stmt->fetchAll();
    
    echo json_encode($users);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
