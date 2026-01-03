<?php
/**
 * Reset password with token
 * POST /api/auth/reset-password.php
 * 
 * Body: { token, password }
 * 
 * Verifies JWT token and sets new password
 */

session_start();
require_once '../../config.php';
require_once '../helpers/jwt.php';

header('Content-Type: application/json');

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';
$password = $data['password'] ?? '';

// Validate input
if (!$token) {
    http_response_code(400);
    echo json_encode(['error' => 'Token chybí']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Heslo musí mít alespoň 6 znaků']);
    exit;
}

try {
    // Verify token
    $payload = verifyJWT($token, $jwtSecret);
    
    if (!$payload) {
        http_response_code(400);
        echo json_encode(['error' => 'Neplatný nebo expirovaný token']);
        exit;
    }
    
    // Check purpose (can be password_reset or setup_password)
    $validPurposes = ['password_reset', 'setup_password'];
    if (!isset($payload['purpose']) || !in_array($payload['purpose'], $validPurposes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Neplatný token']);
        exit;
    }
    
    $email = $payload['email'] ?? '';
    
    if (!$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Neplatný token']);
        exit;
    }
    
    // Update password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->execute([$passwordHash, $email]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Uživatel nenalezen']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Heslo bylo úspěšně změněno'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba serveru']);
}
