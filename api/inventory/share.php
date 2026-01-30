<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$userId = $_SESSION['user_id'];
$inventoryId = $_SESSION['inventory_id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

if (!$inventoryId) {
    http_response_code(400);
    echo json_encode(['error' => 'Žádná aktivní evidence']);
    exit;
}

// Create a new share link for the currently active inventory
try {
    // Use active inventory and verify current user is owner
    $stmt = $pdo->prepare("SELECT id FROM inventories WHERE id = ? AND owner_id = ?");
    $stmt->execute([$inventoryId, $userId]);
    $inv = $stmt->fetch();
    if (!$inv) {
        http_response_code(403);
        echo json_encode(['error' => 'Můžete sdílet pouze vlastní evidenci']);
        exit;
    }
    
    // Check if code exists
    $stmt = $pdo->prepare("SELECT access_code FROM inventory_access WHERE inventory_id = ? LIMIT 1");
    $stmt->execute([$inv['id']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo json_encode(['code' => $existing['access_code']]);
    } else {
        $code = bin2hex(random_bytes(8)); // 16 chars
        $stmt = $pdo->prepare("INSERT INTO inventory_access (inventory_id, access_code, permission) VALUES (?, ?, 'write')");
        $stmt->execute([$inv['id'], $code]);
        echo json_encode(['code' => $code]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
