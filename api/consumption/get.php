<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['inventory_id'])) {
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
    $db = getDBConnection();
    $consumptionId = $_GET['id'];
    $inventoryId = $_SESSION['inventory_id'];
    
    // Get consumption record
    $stmt = $db->prepare("
        SELECT cl.id, cl.consumed_weight, cl.consumption_date, cl.note,
               f.id as filament_id, f.manufacturer, f.material, f.color
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
