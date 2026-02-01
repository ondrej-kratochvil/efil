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

    // Název výrobce: schválená verze, nebo návrh pro autora (COALESCE proposal, approved). Odvozené tabulky zaručí jeden záznam výrobce na manufacturer_id.
    $sqlFil = "
        SELECT
            f.id, f.user_display_id, f.material as mat,
            COALESCE(m_proposal.name, m_approved.name) AS man,
            f.manufacturer_id AS man_id,
            f.color_name as color, f.color_hex as hex, f.location as loc, f.price, f.seller, f.purchase_date as date,
            f.spool_type_id as spool_id,
            (SELECT COALESCE(st2.weight_grams, 0) FROM spool_types st2 WHERE st2.spool_type_id = f.spool_type_id AND st2.approved = 1 AND st2.invalidated_at IS NULL LIMIT 1) AS spool_weight,
            f.initial_weight_grams,
            (f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0)) AS g
        FROM filaments f
        LEFT JOIN consumption_log cl ON f.id = cl.filament_id
        LEFT JOIN (SELECT manufacturer_id, MAX(id) AS mid FROM manufacturers WHERE approved = 1 AND invalidated_at IS NULL GROUP BY manufacturer_id) m_approved_ids ON f.manufacturer_id = m_approved_ids.manufacturer_id
        LEFT JOIN manufacturers m_approved ON m_approved.id = m_approved_ids.mid AND m_approved.manufacturer_id = m_approved_ids.manufacturer_id
        LEFT JOIN (SELECT manufacturer_id, created_by, MAX(id) AS mid FROM manufacturers WHERE approved = 0 AND invalidated_at IS NULL GROUP BY manufacturer_id, created_by) m_proposal_ids ON f.manufacturer_id = m_proposal_ids.manufacturer_id AND m_proposal_ids.created_by = ?
        LEFT JOIN manufacturers m_proposal ON m_proposal.id = m_proposal_ids.mid AND m_proposal.manufacturer_id = m_proposal_ids.manufacturer_id AND m_proposal.created_by = m_proposal_ids.created_by
        WHERE f.inventory_id = ?
        GROUP BY f.id
        ORDER BY g DESC
    ";

    $stmt = $pdo->prepare($sqlFil);
    $stmt->execute([$userId, $invId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    $msg = $e->getMessage();
    if (stripos($msg, 'spool_types') !== false && (stripos($msg, "doesn't exist") !== false || stripos($msg, 'exist') !== false)) {
        http_response_code(503);
        echo json_encode([
            'error' => 'Databázové schéma vyžaduje migraci.',
            'migration' => 'Spusťte dev/sql/migrate_spool_types_versioned.php',
        ]);
        exit;
    }
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
