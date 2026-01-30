<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/inventory.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $userId = (int) $_SESSION['user_id'];
    $invId = getInventoryIdForUser($pdo, $userId);

    if ($invId === null) {
        echo json_encode([]);
        exit;
    }

    // spool_weight: one-to-one via f.spool_type_id → scalar subquery (no JOIN to avoid redundant MAX)
    $sqlFil = "
        SELECT
            f.id, f.user_display_id, f.material as mat, f.manufacturer as man, f.color_name as color,
            f.color_hex as hex, f.location as loc, f.price, f.seller, f.purchase_date as date,
            f.spool_type_id as spool_id,
            (SELECT COALESCE(sl2.weight_grams, 0) FROM spool_library sl2 WHERE sl2.id = f.spool_type_id LIMIT 1) AS spool_weight,
            f.initial_weight_grams,
            (f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0)) AS g
        FROM filaments f
        LEFT JOIN consumption_log cl ON f.id = cl.filament_id
        WHERE f.inventory_id = ?
        GROUP BY f.id
        ORDER BY g DESC
    ";

    $stmt = $pdo->prepare($sqlFil);
    $stmt->execute([$invId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
