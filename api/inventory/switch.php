<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['inventory_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing inventory_id']);
    exit;
}

try {
    $db = getDBConnection();
    $inventoryId = $data['inventory_id'];
    $userId = $_SESSION['user_id'];
    
    // Check if user has access to this inventory
    $stmt = $db->prepare("
        SELECT im.role, i.name, i.is_demo
        FROM inventory_members im
        INNER JOIN inventories i ON im.inventory_id = i.id
        WHERE im.user_id = ? AND im.inventory_id = ?
    ");
    $stmt->execute([$userId, $inventoryId]);
    $access = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If not member, check if user is admin_efil
    if (!$access) {
        $stmtUser = $db->prepare("SELECT system_role FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['system_role'] === 'admin_efil') {
            // Admin can access any inventory
            $stmt = $db->prepare("SELECT name, is_demo FROM inventories WHERE id = ?");
            $stmt->execute([$inventoryId]);
            $inv = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($inv) {
                $access = [
                    'role' => 'manage',
                    'name' => $inv['name'],
                    'is_demo' => $inv['is_demo']
                ];
            }
        }
    }
    
    if (!$access) {
        http_response_code(403);
        echo json_encode(['error' => 'Nemáte přístup k této evidenci']);
        exit;
    }
    
    // Switch inventory
    $_SESSION['inventory_id'] = $inventoryId;
    $_SESSION['inventory_role'] = $access['role'];
    $_SESSION['is_demo'] = $access['is_demo'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Evidence přepnuta',
        'inventory' => [
            'id' => $inventoryId,
            'name' => $access['name'],
            'is_demo' => $access['is_demo']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
