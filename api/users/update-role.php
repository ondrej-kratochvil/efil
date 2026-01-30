<?php
declare(strict_types=1);

/**
 * Update user role in inventory
 * POST /api/users/update-role.php
 * 
 * Body: { user_id, role }
 */

session_start();
require_once '../../config.php';
require_once '../helpers/inventory.php';
require_once '../helpers/email.php';

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

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$targetUserId = intval($data['user_id'] ?? 0);
$newRole = $data['role'] ?? '';

// Validate input
if (!$targetUserId) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatné ID uživatele']);
    exit;
}

if (!in_array($newRole, ['read', 'write', 'manage'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatná role']);
    exit;
}

try {
    $inventory = requireInventoryManageAccess($pdo, (int) $inventoryId, (int) $userId);

    // Cannot change owner's role
    if ($targetUserId === (int) $inventory['owner_id']) {
        http_response_code(400);
        echo json_encode(['error' => 'Nelze změnit oprávnění vlastníka']);
        exit;
    }
    
    // Cannot change your own role (unless admin)
    if ($targetUserId === $userId && !$inventory['is_admin']) {
        http_response_code(400);
        echo json_encode(['error' => 'Nelze změnit vlastní oprávnění']);
        exit;
    }
    
    // Update role
    $stmt = $pdo->prepare("UPDATE inventory_members SET role = ? WHERE inventory_id = ? AND user_id = ?");
    $stmt->execute([$newRole, $inventoryId, $targetUserId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Uživatel nenalezen v evidenci']);
        exit;
    }
    
    // Get target user email for notification
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$targetUserId]);
    $targetUser = $stmt->fetch();
    
    // Send notification email
    if ($targetUser) {
        sendRoleChangeEmail($targetUser['email'], $inventory['name'], $newRole, $smtpConfig);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Oprávnění změněna'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
