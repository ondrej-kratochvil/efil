<?php
/**
 * Change user password
 * POST /api/account/change-password.php
 * 
 * Body: { current_password, new_password }
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

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$currentPassword = $data['current_password'] ?? '';
$newPassword = $data['new_password'] ?? '';

// Validate input
if (strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Nové heslo musí mít alespoň 6 znaků']);
    exit;
}

try {
    // Get current password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Uživatel nenalezen']);
        exit;
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password_hash'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Současné heslo je nesprávné']);
        exit;
    }
    
    // Update password
    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$newPasswordHash, $userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Heslo bylo změněno'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba serveru']);
}
