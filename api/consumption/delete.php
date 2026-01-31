<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/inventory.php';
require_once __DIR__ . '/../helpers/demo.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing consumption ID']);
    exit;
}

try {
    $consumptionId = $data['id'];
    $userId = (int) $_SESSION['user_id'];
    $inventoryId = getInventoryIdForUser($pdo, $userId);
    if ($inventoryId === null) {
        http_response_code(404);
        echo json_encode(['error' => 'No inventory found']);
        exit;
    }

    // Verify access - user must have write/manage permission to the inventory
    // Check if user is owner OR has write/manage role in inventory_members
    $stmt = $pdo->prepare("
        SELECT cl.id, cl.amount_grams, cl.filament_id, i.is_demo
        FROM consumption_log cl
        INNER JOIN filaments f ON cl.filament_id = f.id
        INNER JOIN inventories i ON f.inventory_id = i.id
        WHERE cl.id = ? AND i.id = ? AND (
            i.owner_id = ?
            OR EXISTS (
                SELECT 1 FROM inventory_members im
                WHERE im.inventory_id = i.id
                AND im.user_id = ?
                AND im.role IN ('write', 'manage')
            )
        )
    ");
    $stmt->execute([$consumptionId, $inventoryId, $userId, $userId]);
    $consumption = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$consumption) {
        http_response_code(403);
        echo json_encode(['error' => 'Nemáte oprávnění k tomuto záznamu']);
        exit;
    }

    checkDemoModeAccess($pdo, $userId, $consumption['is_demo'] ?? null, 'V demo režimu nelze upravovat data');

    // Weight is computed dynamically: initial_weight_grams + SUM(consumption_log.amount_grams).
    // Deleting the record automatically updates the derived weight; no filaments UPDATE.

    // Delete consumption record
    $stmt = $pdo->prepare("DELETE FROM consumption_log WHERE id = ?");
    $stmt->execute([$consumptionId]);

    echo json_encode(['success' => true, 'message' => 'Záznam smazán']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
