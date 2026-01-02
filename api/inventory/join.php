<?php
require_once __DIR__ . '/../../config.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';

if (!$code) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing code']);
    exit;
}

try {
    // Find invitation
    $stmt = $pdo->prepare("SELECT inventory_id, permission FROM inventory_access WHERE access_code = ?");
    $stmt->execute([$code]);
    $invite = $stmt->fetch();
    
    if (!$invite) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid code']);
        exit;
    }
    
    // Add member
    // Check if already member or owner
    $stmtCheck = $pdo->prepare("SELECT id FROM inventories WHERE id = ? AND owner_id = ?");
    $stmtCheck->execute([$invite['inventory_id'], $userId]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['message' => 'You are already the owner']);
        exit;
    }
    
    // Insert into members (ignore if exists)
    $stmt = $pdo->prepare("INSERT IGNORE INTO inventory_members (inventory_id, user_id, role) VALUES (?, ?, ?)");
    $stmt->execute([$invite['inventory_id'], $userId, $invite['permission']]);
    
    echo json_encode(['message' => 'Joined successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
