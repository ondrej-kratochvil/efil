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

// Create a new share link
try {
    // Get user's inventory
    $stmt = $pdo->prepare("SELECT id FROM inventories WHERE owner_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $inv = $stmt->fetch();
    if (!$inv) { http_response_code(404); exit; }
    
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
