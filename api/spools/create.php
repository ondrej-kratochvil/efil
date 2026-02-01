<?php
declare(strict_types=1);

/**
 * Vytvoření nového soukromého typu cívky (public=0, approved=1).
 * POST /api/spools/create.php
 * Body: weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, manufacturer_ids (array)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/spool_types.php';
require_once __DIR__ . '/../helpers/manufacturers.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nepřihlášen']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatný JSON']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

$weightGrams = isset($data['weight_grams']) && $data['weight_grams'] !== '' ? (int) $data['weight_grams'] : null;
$color = isset($data['color']) ? trim((string) $data['color']) : null;
$material = isset($data['material']) ? trim((string) $data['material']) : null;
$outerDiameter = isset($data['outer_diameter_mm']) && $data['outer_diameter_mm'] !== '' ? (int) $data['outer_diameter_mm'] : null;
$width = isset($data['width_mm']) && $data['width_mm'] !== '' ? (int) $data['width_mm'] : null;
$visualDescription = isset($data['visual_description']) ? trim((string) $data['visual_description']) : null;

$manufData = $data['manufacturer_ids'] ?? $data['manufacturer_names'] ?? [];

// Barva a materiál jsou povinné
if ($color === null || trim((string) $color) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Barva je povinná.']);
    exit;
}
if ($material === null || trim((string) $material) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Materiál je povinný.']);
    exit;
}
$validManufIds = [];

try {
    if (is_array($manufData) && count($manufData) > 0) {
        $isNames = !is_numeric($manufData[0]);
        if ($isNames) {
            $stmtGetId = $pdo->prepare("
                SELECT manufacturer_id FROM manufacturers
                WHERE approved = 1 AND invalidated_at IS NULL AND LOWER(TRIM(name)) = LOWER(?)
                LIMIT 1
            ");
            $notFoundNames = [];
            foreach ($manufData as $manufName) {
                $stmtGetId->execute([trim((string) $manufName)]);
                $manufId = $stmtGetId->fetchColumn();
                if ($manufId !== false) {
                    $validManufIds[] = (int) $manufId;
                } else {
                    $notFoundNames[] = $manufName;
                }
            }
            if (count($notFoundNames) > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Některé výrobce nebyly nalezeny', 'not_found' => $notFoundNames]);
                exit;
            }
        } else {
            $placeholders = implode(',', array_fill(0, count($manufData), '?'));
            $stmtValidate = $pdo->prepare("
                SELECT DISTINCT manufacturer_id FROM manufacturers
                WHERE approved = 1 AND invalidated_at IS NULL AND manufacturer_id IN ($placeholders)
            ");
            $stmtValidate->execute(array_map('intval', $manufData));
            $validManufIds = array_map('intval', $stmtValidate->fetchAll(PDO::FETCH_COLUMN));
            if (count($validManufIds) !== count($manufData)) {
                http_response_code(400);
                echo json_encode(['error' => 'Některé ID výrobců nejsou platné']);
                exit;
            }
        }
    }
    $pdo->beginTransaction();

    $nextId = getNextSpoolTypeId($pdo);
    $stmt = $pdo->prepare("
        INSERT INTO spool_types (spool_type_id, weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, public, approved, created_at, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, 1, NOW(), ?)
    ");
    $stmt->execute([$nextId, $weightGrams, $color, $material, $outerDiameter, $width, $visualDescription, $userId]);

    if (count($validManufIds) > 0) {
        $stmtManuf = $pdo->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
        foreach ($validManufIds as $manufId) {
            $stmtManuf->execute([$nextId, $manufId]);
        }
    }

    $pdo->commit();

    $row = getSpoolTypeCurrentRow($pdo, $nextId, $userId);
    $label = $row !== null ? spoolTypeRowToLabel($row) : 'Typ cívky';

    echo json_encode([
        'success' => true,
        'id' => $nextId,
        'label' => $label,
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
