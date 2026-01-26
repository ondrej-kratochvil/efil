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

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing consumption ID']);
    exit;
}

try {
    $consumptionId = $_GET['id'];
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

    // Get consumption record
    // Return original amount_grams (can be negative for consumption or positive for correction)
    // Frontend should handle display of absolute value and sign
    $stmt = $pdo->prepare("
        SELECT cl.id, cl.amount_grams, ABS(cl.amount_grams) as consumed_weight, cl.consumption_date, cl.description as note,
               f.id as filament_id, f.manufacturer, f.material, f.color_name as color
        FROM consumption_log cl
        INNER JOIN filaments f ON cl.filament_id = f.id
        WHERE cl.id = ? AND f.inventory_id = ?
    ");
    $stmt->execute([$consumptionId, $inventoryId]);
    $consumption = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$consumption) {
        http_response_code(404);
        echo json_encode(['error' => 'Záznam nenalezen']);
        exit;
    }

    echo json_encode($consumption);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
