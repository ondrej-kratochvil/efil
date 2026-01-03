<?php
/**
 * Change user email
 * POST /api/account/change-email.php
 * 
 * Body: { new_email, password }
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
$newEmail = trim($data['new_email'] ?? '');
$password = $data['password'] ?? '';

// Validate input
if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatná emailová adresa']);
    exit;
}

try {
    // Get current user data
    $stmt = $pdo->prepare("SELECT email, password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Uživatel nenalezen']);
        exit;
    }
    
    // Check if email is the same
    if ($user['email'] === $newEmail) {
        http_response_code(400);
        echo json_encode(['error' => 'Nový email je stejný jako současný']);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nesprávné heslo']);
        exit;
    }
    
    // Check if new email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$newEmail, $userId]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Tento email je již používán']);
        exit;
    }
    
    // Update email
    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->execute([$newEmail, $userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Email byl změněn'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba serveru']);
}
