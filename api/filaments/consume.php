<?php
require_once __DIR__ . '/../../config.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$filamentId = $input['filament_id'] ?? null;
$amount = (int)($input['amount'] ?? 0); // Negative for consumption, positive for correction
$description = $input['description'] ?? 'Manual Log';

if (!$filamentId || $amount == 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    // Verify access via inventory (owner or member with write/manage permission) and check if demo
    $stmt = $pdo->prepare("
        SELECT f.id, i.is_demo
        FROM filaments f
        JOIN inventories i ON f.inventory_id = i.id
        WHERE f.id = ? AND (
            i.owner_id = ?
            OR EXISTS (
                SELECT 1 FROM inventory_members im
                WHERE im.inventory_id = i.id
                AND im.user_id = ?
                AND im.role IN ('write', 'manage')
            )
        )
    ");
    $stmt->execute([$filamentId, $userId, $userId]);
    $filamentData = $stmt->fetch();

    if (!$filamentData) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Check if user is admin_efil
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    $isAdmin = ($user && $user['role'] === 'admin_efil');

    // Check if demo mode (and user is not admin)
    if ($filamentData['is_demo'] && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'V demo režimu nelze upravovat data. Vytvořte si vlastní účet pro plný přístup.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO consumption_log (filament_id, amount_grams, description) VALUES (?, ?, ?)");
    $stmt->execute([$filamentId, $amount, $description]);

    echo json_encode(['message' => 'Logged successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
