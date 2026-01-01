<?php
require_once __DIR__ . '/../../config.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Basic implementation: get all filaments for user's inventory
    // Joining inventory_access would be needed for shared inventories in future
    $userId = $_SESSION['user_id'];
    
    // Get user's primary inventory
    $stmtInv = $pdo->prepare("SELECT id FROM inventories WHERE owner_id = ? LIMIT 1");
    $stmtInv->execute([$userId]);
    $inv = $stmtInv->fetch();
    
    if (!$inv) {
        echo json_encode([]);
        exit;
    }

    // Calculate current weight (Netto) = initial + consumption
    // Note: consumption is negative for usage
    $sql = "
        SELECT 
            f.id, 
            f.material as mat, 
            f.manufacturer as man, 
            f.color_name as color, 
            f.color_hex as hex, 
            f.location as loc,
            f.price,
            f.seller,
            f.purchase_date as date,
            (f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0)) as g
        FROM filaments f
        LEFT JOIN consumption_log cl ON f.id = cl.filament_id
        WHERE f.inventory_id = ?
        GROUP BY f.id
        ORDER BY g DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$inv['id']]);
    $filaments = $stmt->fetchAll();

    echo json_encode($filaments);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
