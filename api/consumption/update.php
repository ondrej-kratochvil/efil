<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['inventory_id'])) {
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
    $userId = $_SESSION['user_id'];
    $inventoryId = $_SESSION['inventory_id'];

    // Verify access - user must have write/manage permission to the inventory
    // Check if user is owner OR has write/manage role in inventory_members
    $stmt = $pdo->prepare("
        SELECT cl.id, cl.amount_grams as old_weight, cl.filament_id, i.is_demo
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

    // Check if user is admin_efil
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($user && $user['role'] === 'admin_efil');

    // Check if demo mode (and user is not admin)
    if ($consumption['is_demo'] && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'V demo režimu nelze upravovat data']);
        exit;
    }

    // Update consumption record
    $updates = [];
    $params = [];

    if (isset($data['consumed_weight'])) {
        $newWeight = intval($data['consumed_weight']);
        $updates[] = "amount_grams = ?";
        $params[] = -$newWeight;
        // Weight is computed dynamically: initial_weight_grams + SUM(consumption_log.amount_grams).
        // Updating amount_grams here is enough; no filaments UPDATE.
    }

    if (isset($data['consumption_date'])) {
        $updates[] = "consumption_date = ?";
        $params[] = $data['consumption_date'];
    }

    if (isset($data['note'])) {
        $updates[] = "description = ?";
        $params[] = $data['note'];
    }

    if (count($updates) > 0) {
        $params[] = $consumptionId;
        $sql = "UPDATE consumption_log SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    echo json_encode(['success' => true, 'message' => 'Záznam aktualizován']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
