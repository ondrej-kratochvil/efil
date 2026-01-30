<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

session_start();
header('Content-Type: application/json');

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['password'])) {
    jsonResponse(['error' => 'Missing credentials'], 400);
}

$email = $input['email'];
$password = $input['password'];

try {
    $stmt = $pdo->prepare("SELECT id, email, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $user['password_hash'] !== null && password_verify($password, $user['password_hash'])) {
        // Login success
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        // Set default inventory_id if user has any inventory (owned first, then shared)
        $stmtInv = $pdo->prepare("
            SELECT inv.id, inv.is_demo, inv.role, inv._priority FROM (
                SELECT i.id, i.is_demo, 'owner' as role, 1 as _priority
                FROM inventories i
                WHERE i.owner_id = ?
                UNION
                SELECT i.id, i.is_demo, COALESCE(im.role, 'read') as role, 0 as _priority
                FROM inventories i
                JOIN inventory_members im ON i.id = im.inventory_id
                WHERE im.user_id = ?
            ) AS inv
            ORDER BY inv._priority DESC, inv.id ASC
            LIMIT 1
        ");
        $stmtInv->execute([$user['id'], $user['id']]);
        $inv = $stmtInv->fetch();
        
        if ($inv) {
            $_SESSION['inventory_id'] = $inv['id'];
            $_SESSION['inventory_role'] = $inv['role'];
            $_SESSION['is_demo'] = $inv['is_demo'];
        }

        jsonResponse([
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    } else {
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }

} catch (Exception $e) {
    jsonResponse(['error' => 'Server error'], 500);
}
