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

try {
    $userId = (int) $_SESSION['user_id'];
    $inventoryId = getInventoryIdForUser($pdo, $userId, true);
    if ($inventoryId === null) {
        http_response_code(404);
        echo json_encode(['error' => 'No inventory found']);
        exit;
    }

    // Check if filtering by specific filament
    $filamentId = isset($_GET['filament_id']) ? intval($_GET['filament_id']) : null;

    // Build query
    if ($filamentId) {
        // Get consumption for specific filament
        // Use INNER JOIN to ensure filament exists and belongs to the inventory
        try {
            $stmt = $pdo->prepare("
                SELECT cl.id, cl.amount_grams, ABS(cl.amount_grams) as consumed_weight, cl.consumption_date, cl.description as note, cl.created_at,
                       f.manufacturer, f.material, f.color_name as color, f.user_display_id, f.location,
                       u.email as created_by_email
                FROM consumption_log cl
                INNER JOIN filaments f ON cl.filament_id = f.id
                LEFT JOIN users u ON cl.created_by = u.id
                WHERE cl.filament_id = ? AND f.inventory_id = ?
                ORDER BY cl.consumption_date DESC, cl.created_at DESC
            ");
            $stmt->execute([$filamentId, $inventoryId]);
        } catch (PDOException $e) {
            error_log("SQL Error in consumption list (filament_id=$filamentId): " . $e->getMessage());
            throw $e;
        }
    } else {
        // Get consumption for entire inventory
        // Use INNER JOIN to ensure filament exists and belongs to the inventory
        try {
            $stmt = $pdo->prepare("
                SELECT cl.id, cl.amount_grams, ABS(cl.amount_grams) as consumed_weight, cl.consumption_date, cl.description as note, cl.created_at,
                       f.manufacturer, f.material, f.color_name as color, f.user_display_id, f.location,
                       u.email as created_by_email
                FROM consumption_log cl
                INNER JOIN filaments f ON cl.filament_id = f.id
                LEFT JOIN users u ON cl.created_by = u.id
                WHERE f.inventory_id = ?
                ORDER BY cl.consumption_date DESC, cl.created_at DESC
                LIMIT 100
            ");
            $stmt->execute([$inventoryId]);
        } catch (PDOException $e) {
            error_log("SQL Error in consumption list (inventory_id=$inventoryId): " . $e->getMessage());
            throw $e;
        }
    }

    $consumptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($consumptions);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
