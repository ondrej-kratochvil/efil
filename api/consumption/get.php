<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/inventory.php';

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
    $userId = (int) $_SESSION['user_id'];
    $inventoryId = getInventoryIdForUser($pdo, $userId);
    if ($inventoryId === null) {
        http_response_code(404);
        echo json_encode(['error' => 'No inventory found']);
        exit;
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
