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

    // Název výrobce: schválená verze, nebo návrh pro autora (COALESCE proposal, approved)
    $sqlFil = "
        SELECT
            f.id, f.user_display_id, f.material as mat,
            COALESCE(m_proposal.name, m_approved.name) AS man,
            f.manufacturer_id AS man_id,
            f.color_name as color, f.color_hex as hex, f.location as loc, f.price, f.seller, f.purchase_date as date,
            f.spool_type_id as spool_id,
            (SELECT COALESCE(sl2.weight_grams, 0) FROM spool_library sl2 WHERE sl2.id = f.spool_type_id LIMIT 1) AS spool_weight,
            f.initial_weight_grams,
            (f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0)) AS g
        FROM filaments f
        LEFT JOIN consumption_log cl ON f.id = cl.filament_id
        LEFT JOIN manufacturers m_approved ON f.manufacturer_id = m_approved.manufacturer_id AND m_approved.approved = 1 AND m_approved.invalidated_at IS NULL
        LEFT JOIN manufacturers m_proposal ON f.manufacturer_id = m_proposal.manufacturer_id AND m_proposal.approved = 0 AND m_proposal.invalidated_at IS NULL AND m_proposal.created_by = ?
        WHERE f.inventory_id = ?
        GROUP BY f.id
        ORDER BY g DESC
    ";

    $stmt = $pdo->prepare($sqlFil);
    $stmt->execute([$userId, $invId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
