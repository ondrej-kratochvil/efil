<?php
declare(strict_types=1);

/**
 * Find-or-create typ cívky podle atributů (legacy endpoint).
 * Hledá schválený typ se stejnými atributy (public=1 nebo created_by=userId); jinak vytvoří nový soukromý.
 * POST /api/spools/save.php
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/spool_types.php';
require_once __DIR__ . '/../helpers/manufacturers.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = (int) $_SESSION['user_id'];

$manufacturerName = isset($input['manufacturer']) ? trim((string) $input['manufacturer']) : null;
$weightGrams = !empty($input['weight_grams']) ? (int) $input['weight_grams'] : null;
$color = isset($input['color']) ? trim((string) $input['color']) : null;
$material = isset($input['material']) ? trim((string) $input['material']) : null;
$outerDiameter = !empty($input['outer_diameter_mm']) ? (int) $input['outer_diameter_mm'] : null;
$width = !empty($input['width_mm']) ? (int) $input['width_mm'] : null;
$description = isset($input['visual_description']) ? trim((string) $input['visual_description']) : null;

if (!$color && !$material && !$outerDiameter && !$width && !$description) {
    http_response_code(400);
    echo json_encode(['error' => 'At least one identifying field (color, material, diameter, width, description) is required']);
    exit;
}

try {
    $manufacturerId = null;
    if ($manufacturerName !== null && $manufacturerName !== '') {
        $stmt = $pdo->prepare("
            SELECT manufacturer_id FROM manufacturers
            WHERE approved = 1 AND invalidated_at IS NULL AND LOWER(TRIM(name)) = LOWER(?)
              AND (public = 1 OR (public = 0 AND created_by = ?))
            LIMIT 1
        ");
        $stmt->execute([$manufacturerName, $userId]);
        $manId = $stmt->fetchColumn();
        if ($manId !== false) {
            $manufacturerId = (int) $manId;
        } else {
            $nextManId = getNextManufacturerId($pdo);
            $stmt = $pdo->prepare("INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_at, created_by) VALUES (?, ?, 0, 1, NOW(), ?)");
            $stmt->execute([$nextManId, $manufacturerName, $userId]);
            $manufacturerId = $nextManId;
        }
    }

    $stmt = $pdo->prepare("
        SELECT st.spool_type_id
        FROM spool_types st
        WHERE st.approved = 1 AND st.invalidated_at IS NULL
          AND (st.public = 1 OR (st.public = 0 AND st.created_by = ?))
          AND (st.weight_grams <=> ?)
          AND (COALESCE(st.color, '') <=> ?)
          AND (COALESCE(st.material, '') <=> ?)
          AND (st.outer_diameter_mm <=> ?)
          AND (st.width_mm <=> ?)
          AND (COALESCE(st.visual_description, '') <=> ?)
        LIMIT 1
    ");
    $stmt->execute([$userId, $weightGrams, $color ?? '', $material ?? '', $outerDiameter, $width, $description ?? '']);
    $existing = $stmt->fetchColumn();
    if ($existing !== false) {
        $spoolTypeId = (int) $existing;
        $row = getSpoolTypeCurrentRow($pdo, $spoolTypeId, $userId);
        $label = $row !== null ? spoolTypeRowToLabel($row) : 'Typ cívky';
        echo json_encode(['id' => $spoolTypeId, 'message' => 'Spool already exists', 'label' => $label]);
        exit;
    }

    $nextId = getNextSpoolTypeId($pdo);
    $stmt = $pdo->prepare("
        INSERT INTO spool_types (spool_type_id, weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, public, approved, created_at, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, 1, NOW(), ?)
    ");
    $stmt->execute([$nextId, $weightGrams, $color, $material, $outerDiameter, $width, $description, $userId]);

    if ($manufacturerId !== null) {
        $stmt = $pdo->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
        $stmt->execute([$nextId, $manufacturerId]);
    }

    $row = getSpoolTypeCurrentRow($pdo, $nextId, $userId);
    $label = $row !== null ? spoolTypeRowToLabel($row) : 'Typ cívky';
    echo json_encode([
        'id' => $nextId,
        'weight_grams' => $weightGrams,
        'color' => $color,
        'material' => $material,
        'outer_diameter_mm' => $outerDiameter,
        'width_mm' => $width,
        'visual_description' => $description,
        'label' => $label,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
