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
        SELECT cl.id, cl.consumed_weight as old_weight, cl.filament_id, f.current_weight, i.is_demo
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
    
    // Update consumption record
    $updates = [];
    $params = [];
    
    if (isset($data['consumed_weight'])) {
        $updates[] = "consumed_weight = ?";
        $newWeight = intval($data['consumed_weight']);
        $params[] = $newWeight;
        
        // Adjust filament weight: add back old consumption, subtract new consumption
        $weightDiff = $consumption['old_weight'] - $newWeight;
        $stmt = $db->prepare("UPDATE filaments SET current_weight = current_weight + ? WHERE id = ?");
        $stmt->execute([$weightDiff, $consumption['filament_id']]);
    }
    
    if (isset($data['consumption_date'])) {
        $updates[] = "consumption_date = ?";
        $params[] = $data['consumption_date'];
    }
    
    if (isset($data['note'])) {
        $updates[] = "note = ?";
        $params[] = $data['note'];
    }
    
    if (count($updates) > 0) {
        $params[] = $consumptionId;
        $sql = "UPDATE consumption_log SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }
    
    echo json_encode(['success' => true, 'message' => 'Záznam aktualizován']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
