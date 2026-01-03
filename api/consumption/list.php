<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['inventory_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = getDBConnection();
    $inventoryId = $_SESSION['inventory_id'];
    
    // Check if filtering by specific filament
    $filamentId = isset($_GET['filament_id']) ? intval($_GET['filament_id']) : null;
    
    // Build query
    if ($filamentId) {
        // Get consumption for specific filament
        $stmt = $db->prepare("
            SELECT cl.id, cl.consumed_weight, cl.consumption_date, cl.note, cl.created_at,
                   f.manufacturer, f.material, f.color, f.user_display_id, f.location,
                   u.email as created_by_email
            FROM consumption_log cl
            LEFT JOIN filaments f ON cl.filament_id = f.id
            LEFT JOIN users u ON cl.created_by = u.id
            WHERE cl.filament_id = ? AND f.inventory_id = ?
            ORDER BY cl.consumption_date DESC, cl.created_at DESC
        ");
        $stmt->execute([$filamentId, $inventoryId]);
    } else {
        // Get consumption for entire inventory
        $stmt = $db->prepare("
            SELECT cl.id, cl.consumed_weight, cl.consumption_date, cl.note, cl.created_at,
                   f.manufacturer, f.material, f.color, f.user_display_id, f.location,
                   u.email as created_by_email
            FROM consumption_log cl
            LEFT JOIN filaments f ON cl.filament_id = f.id
            LEFT JOIN users u ON cl.created_by = u.id
            WHERE f.inventory_id = ?
            ORDER BY cl.consumption_date DESC, cl.created_at DESC
            LIMIT 100
        ");
        $stmt->execute([$inventoryId]);
    }
    
    $consumptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($consumptions);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
