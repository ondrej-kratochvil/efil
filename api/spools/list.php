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

    if (empty($spools)) {
        echo json_encode([]);
        exit;
    }

    // Avoid N+1: load all manufacturers for all spools in a single query
    $spoolIds = array_column($spools, 'id');

    $placeholders = implode(',', array_fill(0, count($spoolIds), '?'));
    $sqlManuf = "
        SELECT sm.spool_id, m.id, m.name
        FROM spool_manufacturer sm
        INNER JOIN manufacturers m ON m.id = sm.manufacturer_id
        WHERE sm.spool_id IN ($placeholders)
        ORDER BY m.name
    ";
    $stmtManuf = $pdo->prepare($sqlManuf);
    $stmtManuf->execute($spoolIds);
    $rows = $stmtManuf->fetchAll(PDO::FETCH_ASSOC);

    $manBySpool = [];
    foreach ($rows as $row) {
        $spoolId = (int)$row['spool_id'];
        if (!isset($manBySpool[$spoolId])) {
            $manBySpool[$spoolId] = [];
        }
        $manBySpool[$spoolId][] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
        ];
    }

    foreach ($spools as &$spool) {
        $id = (int)$spool['id'];
        $spool['manufacturers'] = $manBySpool[$id] ?? [];
    }
    unset($spool);

    echo json_encode($spools);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
