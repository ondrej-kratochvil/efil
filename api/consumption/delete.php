<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

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
    $userId = $_SESSION['user_id'];
    
    // Get inventory_id from session, or get first available inventory
    $inventoryId = $_SESSION['inventory_id'] ?? null;
    
    if (!$inventoryId) {
        // Get first available inventory for user
        $stmtInv = $pdo->prepare("
            SELECT i.id
            FROM inventories i
            WHERE i.owner_id = ?
            UNION
            SELECT i.id
            FROM inventories i
            JOIN inventory_members im ON i.id = im.inventory_id
            WHERE im.user_id = ?
            LIMIT 1
        ");
        $stmtInv->execute([$userId, $userId]);
        $inv = $stmtInv->fetch(PDO::FETCH_ASSOC);
        
        if (!$inv) {
            http_response_code(404);
            echo json_encode(['error' => 'No inventory found']);
            exit;
        }
        
        $inventoryId = $inv['id'];
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

    // Check if user is admin_efil
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($user && $user['role'] === 'admin_efil');

    // Check if demo mode (and user is not admin)
    // MySQL BOOLEAN is TINYINT(1), so we need to check for 1 or '1'
    $isDemo = ($consumption['is_demo'] === 1 || $consumption['is_demo'] === '1' || (bool)$consumption['is_demo']);
    if ($isDemo && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'V demo režimu nelze upravovat data']);
        exit;
    }

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
