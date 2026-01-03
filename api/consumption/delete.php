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
    $db = getDBConnection();
    $consumptionId = $data['id'];
    $userId = $_SESSION['user_id'];
    $inventoryId = $_SESSION['inventory_id'];
    
    // Verify access - user must have write/manage permission to the inventory
    $stmt = $db->prepare("
        SELECT cl.id, cl.consumed_weight, cl.filament_id, i.is_demo
        FROM consumption_log cl
        INNER JOIN filaments f ON cl.filament_id = f.id
        INNER JOIN inventories i ON f.inventory_id = i.id
        WHERE cl.id = ? AND i.id = ?
    ");
    $stmt->execute([$consumptionId, $inventoryId]);
    $consumption = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$consumption) {
        http_response_code(404);
        echo json_encode(['error' => 'Záznam čerpání nenalezen']);
        exit;
    }
    
    // Check if user is admin_efil
    $stmt = $db->prepare("SELECT system_role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($user && $user['system_role'] === 'admin_efil');
    
    // Check if demo mode (and user is not admin)
    if ($consumption['is_demo'] && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'V demo režimu nelze upravovat data']);
        exit;
    }
    
    // Return consumed weight back to filament
    $stmt = $db->prepare("UPDATE filaments SET current_weight = current_weight + ? WHERE id = ?");
    $stmt->execute([$consumption['consumed_weight'], $consumption['filament_id']]);
    
    // Delete consumption record
    $stmt = $db->prepare("DELETE FROM consumption_log WHERE id = ?");
    $stmt->execute([$consumptionId]);
    
    echo json_encode(['success' => true, 'message' => 'Záznam smazán']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
