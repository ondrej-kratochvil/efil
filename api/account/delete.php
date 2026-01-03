<?php
/**
 * Delete user account
 * POST /api/account/delete.php
 * 
 * Body: { password, confirmation }
 * 
 * Deletes user account and all owned inventories (CASCADE will handle members, filaments, etc.)
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
$password = $data['password'] ?? '';
$confirmation = $data['confirmation'] ?? '';

// Validate confirmation
if ($confirmation !== 'SMAZAT') {
    http_response_code(400);
    echo json_encode(['error' => 'Pro potvrzení zadejte slovo SMAZAT']);
    exit;
}

try {
    // Get user data
    $stmt = $pdo->prepare("SELECT password_hash, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Uživatel nenalezen']);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nesprávné heslo']);
        exit;
    }
    
    // Delete user (CASCADE will delete inventories, members, consumption logs, etc.)
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Destroy session
    session_destroy();
    
    echo json_encode([
        'success' => true,
        'message' => 'Účet byl smazán'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba serveru']);
}
