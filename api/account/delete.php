<?php
declare(strict_types=1);

/**
 * Delete user account
 * POST /api/account/delete.php
 * 
 * Body: { password, confirmation }
 * 
 * Deletes user account and owned inventories (CASCADE: members, filaments, consumption_log).
 * manufacturers.created_by and spool_types.created_by use ON DELETE RESTRICT; if the user
 * has created any, deletion fails and a clear error is returned.
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
    
    if ($user['password_hash'] === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Účet nemá nastavené heslo. Použijte odkaz z e-mailu pro první nastavení hesla.']);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nesprávné heslo']);
        exit;
    }
    
    // Delete user (CASCADE: inventories, members, filaments, consumption_log; RESTRICT: manufacturers/spool_types created_by)
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Účet se nepodařilo smazat.']);
        exit;
    }

    // Destroy session
    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Účet byl smazán'
    ]);

} catch (PDOException $e) {
    $code = $e->getCode();
    $msg = $e->getMessage();
    // Foreign key constraint (MySQL 1451, SQLSTATE 23000) – uživatel má vytvořené výrobce nebo typy cívek
    if ($code === '23000' || (is_int($code) && $code === 1451) || str_contains($msg, '1451') || str_contains($msg, 'foreign key')) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Účet nelze smazat, protože máte vytvořené záznamy (výrobci nebo typy cívek). Nejprve je odstraňte nebo převeďte na jiného uživatele.',
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Chyba serveru']);
    }
}
