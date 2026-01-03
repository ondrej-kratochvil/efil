<?php
/**
 * Remove user from inventory
 * POST /api/users/remove.php
 * 
 * Body: { user_id }
 */

session_start();
require_once '../../config.php';
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

// Validate input
if (!$targetUserId) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatné ID uživatele']);
    exit;
}

try {
    // Check if current user has manage permission
    $stmt = $pdo->prepare("
        SELECT role FROM inventory_members 
        WHERE inventory_id = ? AND user_id = ?
    ");
    $stmt->execute([$inventoryId, $userId]);
    $member = $stmt->fetch();
    
    // Check if current user is owner
    $stmt = $pdo->prepare("SELECT owner_id, name FROM inventories WHERE id = ?");
    $stmt->execute([$inventoryId]);
    $inventory = $stmt->fetch();
    $isOwner = ($inventory && $inventory['owner_id'] == $userId);
    
    // Check if current user is admin_efil
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    $isAdmin = ($user && $user['role'] === 'admin_efil');
    
    // Only owner, manage role, or admin can remove users
    if (!$isOwner && !$isAdmin && (!$member || $member['role'] !== 'manage')) {
        http_response_code(403);
        echo json_encode(['error' => 'Nedostatečná oprávnění']);
        exit;
    }
    
    // Cannot remove owner
    if ($targetUserId == $inventory['owner_id']) {
        http_response_code(400);
        echo json_encode(['error' => 'Nelze odebrat vlastníka evidence']);
        exit;
    }
    
    // Cannot remove yourself (unless admin)
    if ($targetUserId == $userId && !$isAdmin) {
        http_response_code(400);
        echo json_encode(['error' => 'Nelze odebrat sám sebe']);
        exit;
    }
    
    // Get target user email for notification
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$targetUserId]);
    $targetUser = $stmt->fetch();
    
    // Remove from inventory
    $stmt = $pdo->prepare("DELETE FROM inventory_members WHERE inventory_id = ? AND user_id = ?");
    $stmt->execute([$inventoryId, $targetUserId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Uživatel nenalezen v evidenci']);
        exit;
    }
    
    // Send notification email
    if ($targetUser) {
        sendRemovalEmail($targetUser['email'], $inventory['name'], $smtpConfig);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Uživatel odebrán'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
