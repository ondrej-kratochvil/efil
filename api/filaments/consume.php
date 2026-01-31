<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/demo.php';

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
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}
$userId = $_SESSION['user_id'];
$filamentId = $input['filament_id'] ?? null;
$amount = isset($input['amount_grams']) ? (int)$input['amount_grams'] : ((int)($input['amount'] ?? 0)); // Negative for consumption, positive for correction
$description = $input['description'] ?? '';
$consumptionDate = $input['consumption_date'] ?? date('Y-m-d');

if (!$filamentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing filament_id']);
    exit;
}

if ($amount == 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Amount cannot be zero']);
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

    checkDemoModeAccess($pdo, (int) $userId, $filamentData['is_demo'] ?? null);

    // Log all weight changes: negative = consumption, positive = correction/addition.
    // current_weight = initial_weight_grams + SUM(consumption_log.amount_grams). Audit trail requires every change.
    $stmt = $pdo->prepare("INSERT INTO consumption_log (filament_id, amount_grams, description, consumption_date, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$filamentId, $amount, $description, $consumptionDate, $userId]);

    echo json_encode(['success' => true, 'message' => 'Logged successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
