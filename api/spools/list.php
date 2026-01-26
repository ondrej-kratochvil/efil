<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

try {
    $userId = $_SESSION['user_id'];

    // Get all spools (standard + user's custom ones)
    $sql = "
        SELECT s.id, s.weight_grams, s.color, s.material, s.outer_diameter_mm, s.width_mm, s.visual_description, s.created_by
        FROM spool_library s
        WHERE s.created_by IS NULL OR s.created_by = ?
        ORDER BY s.color, s.material, s.outer_diameter_mm, s.weight_grams
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $spools = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For each spool, get associated manufacturers
    $stmtManuf = $pdo->prepare("
        SELECT m.id, m.name
        FROM manufacturers m
        INNER JOIN spool_manufacturer sm ON m.id = sm.manufacturer_id
        WHERE sm.spool_id = ?
    ");

    foreach ($spools as &$spool) {
        $stmtManuf->execute([$spool['id']]);
        $spool['manufacturers'] = $stmtManuf->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($spools);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
