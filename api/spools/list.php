<?php
declare(strict_types=1);

/**
 * Seznam typů cívek (veřejné + vlastní), stejný vzor jako výrobci.
 * GET /api/spools/list.php
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/spool_types.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nepřihlášen']);
    exit;
}

try {
    $userId = (int) $_SESSION['user_id'];

    $options = getSpoolTypesForOptions($pdo, $userId);
    if (empty($options)) {
        echo json_encode([]);
        exit;
    }

    $spoolIds = array_column($options, 'id');
    $placeholders = implode(',', array_fill(0, count($spoolIds), '?'));
    $sqlManuf = "
        SELECT sm.spool_id, m.manufacturer_id AS id, m.name
        FROM spool_manufacturer sm
        INNER JOIN (SELECT manufacturer_id, MAX(id) AS mid FROM manufacturers WHERE approved = 1 AND invalidated_at IS NULL GROUP BY manufacturer_id) m_approved_ids ON sm.manufacturer_id = m_approved_ids.manufacturer_id
        INNER JOIN manufacturers m ON m.id = m_approved_ids.mid AND m.manufacturer_id = m_approved_ids.manufacturer_id
        WHERE sm.spool_id IN ($placeholders)
        ORDER BY m.name
    ";
    $stmtManuf = $pdo->prepare($sqlManuf);
    $stmtManuf->execute($spoolIds);
    $rows = $stmtManuf->fetchAll(PDO::FETCH_ASSOC);

    $manBySpool = [];
    foreach ($rows as $row) {
        $spoolId = (int) $row['spool_id'];
        if (!isset($manBySpool[$spoolId])) {
            $manBySpool[$spoolId] = [];
        }
        $manBySpool[$spoolId][] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
        ];
    }

    $spools = [];
    foreach ($options as $opt) {
        $id = (int) $opt['id'];
        $spools[] = [
            'id' => $id,
            'weight_grams' => $opt['weight_grams'],
            'color' => $opt['color'],
            'material' => $opt['material'],
            'outer_diameter_mm' => $opt['outer_diameter_mm'],
            'width_mm' => $opt['width_mm'],
            'visual_description' => $opt['visual_description'],
            'label' => $opt['label'],
            'public' => (int) ($opt['public'] ?? 0),
            'created_by' => $opt['created_by'] ?? null,
            'manufacturers' => $manBySpool[$id] ?? [],
        ];
    }

    echo json_encode($spools);

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
}
