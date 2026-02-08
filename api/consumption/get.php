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
    $consumptionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($consumptionId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid consumption ID']);
        exit;
    }
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
               f.id as filament_id, COALESCE(m_proposal.name, m_approved.name) AS manufacturer, f.material, f.color_name as color
        FROM consumption_log cl
        INNER JOIN filaments f ON cl.filament_id = f.id
        LEFT JOIN (SELECT manufacturer_id, MAX(id) AS mid FROM manufacturers WHERE approved = 1 AND invalidated_at IS NULL GROUP BY manufacturer_id) m_approved_ids ON f.manufacturer_id = m_approved_ids.manufacturer_id
        LEFT JOIN manufacturers m_approved ON m_approved.id = m_approved_ids.mid AND m_approved.manufacturer_id = m_approved_ids.manufacturer_id
        LEFT JOIN (SELECT manufacturer_id, created_by, MAX(id) AS mid FROM manufacturers WHERE approved = 0 AND invalidated_at IS NULL GROUP BY manufacturer_id, created_by) m_proposal_ids ON f.manufacturer_id = m_proposal_ids.manufacturer_id AND m_proposal_ids.created_by = ?
        LEFT JOIN manufacturers m_proposal ON m_proposal.id = m_proposal_ids.mid AND m_proposal.manufacturer_id = m_proposal_ids.manufacturer_id AND m_proposal.created_by = m_proposal_ids.created_by
        WHERE cl.id = ? AND f.inventory_id = ?
    ");
    $stmt->execute([$userId, $consumptionId, $inventoryId]);
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
