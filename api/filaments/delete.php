<?php
/**
 * Delete filament
 * POST /api/filaments/delete.php
 * 
 * Body: { id }
 * 
 * Deletes filament from inventory (CASCADE will delete consumption_log entries)
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

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$filamentId = intval($data['id'] ?? 0);

if (!$filamentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Chybí ID filamentu']);
    exit;
}

try {
    // Check user permissions
    $stmt = $pdo->prepare("
        SELECT im.role, i.owner_id, i.is_demo
        FROM inventories i
        LEFT JOIN inventory_members im ON im.inventory_id = i.id AND im.user_id = ?
        WHERE i.id = ?
    ");
    $stmt->execute([$userId, $inventoryId]);
    $inventory = $stmt->fetch();
    
    if (!$inventory) {
        http_response_code(404);
        echo json_encode(['error' => 'Evidence nenalezena']);
        exit;
    }
    
    // Check if user is admin_efil
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    $isAdmin = ($user && $user['role'] === 'admin_efil');
    
    // Check if demo mode (and user is not admin)
    if ($inventory['is_demo'] && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'V demo režimu nelze mazat']);
        exit;
    }
    
    $isOwner = ($inventory['owner_id'] == $userId);
    $hasWriteAccess = ($inventory['role'] === 'write' || $inventory['role'] === 'manage');
    
    // User must be owner, have write/manage access, or be admin
    if (!$isOwner && !$hasWriteAccess && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'Nedostatečná oprávnění']);
        exit;
    }
    
    // Verify filament belongs to this inventory
    $stmt = $pdo->prepare("SELECT id FROM filaments WHERE id = ? AND inventory_id = ?");
    $stmt->execute([$filamentId, $inventoryId]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Filament nenalezen']);
        exit;
    }
    
    // Delete filament (CASCADE will delete consumption_log entries)
    $stmt = $pdo->prepare("DELETE FROM filaments WHERE id = ? AND inventory_id = ?");
    $stmt->execute([$filamentId, $inventoryId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Filament smazán'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
