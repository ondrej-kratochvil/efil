<?php
declare(strict_types=1);

/**
 * Úprava typu cívky:
 * - Vlastní soukromý (public=0, created_by=userId): nová verze, stará invalidována.
 * - Veřejný (public=1): běžný uživatel vytvoří návrh (approved=0); admin upraví přímo.
 * POST /api/spools/update.php
 * Body: { "id": spool_type_id (logické), weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, manufacturer_ids? }
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
$spoolTypeId = isset($data['id']) ? (int) $data['id'] : 0;

if ($spoolTypeId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID typu cívky je povinné']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin_efil';

$weightGrams = array_key_exists('weight_grams', $data) ? ($data['weight_grams'] !== null && $data['weight_grams'] !== '' ? (int) $data['weight_grams'] : null) : null;
$color = array_key_exists('color', $data) ? ($data['color'] === '' ? null : trim((string) $data['color'])) : null;
$material = array_key_exists('material', $data) ? ($data['material'] === '' ? null : trim((string) $data['material'])) : null;
$outerDiameter = array_key_exists('outer_diameter_mm', $data) ? ($data['outer_diameter_mm'] !== null && $data['outer_diameter_mm'] !== '' ? (int) $data['outer_diameter_mm'] : null) : null;
$width = array_key_exists('width_mm', $data) ? ($data['width_mm'] !== null && $data['width_mm'] !== '' ? (int) $data['width_mm'] : null) : null;
$visualDescription = array_key_exists('visual_description', $data) ? ($data['visual_description'] === '' ? null : trim((string) $data['visual_description'])) : null;
$manufData = $data['manufacturer_ids'] ?? $data['manufacturer_names'] ?? null;

try {
    $current = getSpoolTypeCurrentRow($pdo, $spoolTypeId, $userId);
    if ($current === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Typ cívky nenalezen']);
        exit;
    }

    $isPublic = (int) ($current['public'] ?? 0) === 1;
    $createdBy = (int) ($current['created_by'] ?? 0);

    $useWeight = $weightGrams !== null;
    $useColor = $color !== null;
    $useMaterial = $material !== null;
    $useOuter = $outerDiameter !== null;
    $useWidth = $width !== null;
    $useDesc = $visualDescription !== null;
    $useManuf = $manufData !== null;
    if (!$useWeight && !$useColor && !$useMaterial && !$useOuter && !$useWidth && !$useDesc && !$useManuf) {
        http_response_code(400);
        echo json_encode(['error' => 'Žádná pole k aktualizaci']);
        exit;
    }

    // Validace výrobců před zápisem (stejně jako v create.php) – žádné tiché přeskočení
    $validManufIds = null;
    if ($useManuf) {
        if (!is_array($manufData) || count($manufData) === 0) {
            $validManufIds = [];
        } else {
            $isNames = !is_numeric($manufData[0]);
            if ($isNames) {
                $notFoundNames = [];
                $validManufIds = [];
                $stmtGetId = $pdo->prepare("SELECT manufacturer_id FROM manufacturers WHERE approved = 1 AND invalidated_at IS NULL AND LOWER(TRIM(name)) = LOWER(?) LIMIT 1");
                foreach ($manufData as $name) {
                    $stmtGetId->execute([trim((string) $name)]);
                    $manufId = $stmtGetId->fetchColumn();
                    if ($manufId !== false) {
                        $validManufIds[] = (int) $manufId;
                    } else {
                        $notFoundNames[] = $name;
                    }
                }
                if (count($notFoundNames) > 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Některé výrobce nebyly nalezeny', 'not_found' => $notFoundNames]);
                    exit;
                }
            } else {
                $placeholders = implode(',', array_fill(0, count($manufData), '?'));
                $stmtValidate = $pdo->prepare("SELECT DISTINCT manufacturer_id FROM manufacturers WHERE approved = 1 AND invalidated_at IS NULL AND manufacturer_id IN ($placeholders)");
                $stmtValidate->execute(array_map('intval', $manufData));
                $validIds = $stmtValidate->fetchAll(PDO::FETCH_COLUMN);
                if (count($validIds) !== count($manufData)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Některé ID výrobců nejsou platné']);
                    exit;
                }
                $validManufIds = array_map('intval', $manufData);
            }
        }
    }

    $w = $useWeight ? $weightGrams : ($current['weight_grams'] ?? null);
    $c = $useColor ? $color : ($current['color'] ?? null);
    $m = $useMaterial ? $material : ($current['material'] ?? null);
    $o = $useOuter ? $outerDiameter : ($current['outer_diameter_mm'] ?? null);
    $wi = $useWidth ? $width : ($current['width_mm'] ?? null);
    $v = $useDesc ? $visualDescription : ($current['visual_description'] ?? null);

    // Barva a materiál musí být vždy vyplněny
    $cTrim = $c !== null ? trim((string) $c) : '';
    $mTrim = $m !== null ? trim((string) $m) : '';
    if ($cTrim === '' || $mTrim === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Barva a materiál jsou povinné.']);
        exit;
    }

    if ($isPublic) {
        if ($isAdmin) {
            $pdo->beginTransaction();
            try {
                $stmtInv = $pdo->prepare("UPDATE spool_types SET invalidated_at = NOW(), invalidated_by = ? WHERE spool_type_id = ? AND invalidated_at IS NULL");
                $stmtInv->execute([$userId, $spoolTypeId]);
                $stmtIns = $pdo->prepare("
                    INSERT INTO spool_types (spool_type_id, weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, public, approved, created_at, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, NOW(), ?)
                ");
                $stmtIns->execute([$spoolTypeId, $w, $c, $m, $o, $wi, $v, $userId]);
                if ($validManufIds !== null) {
                    $stmtDel = $pdo->prepare("DELETE FROM spool_manufacturer WHERE spool_id = ?");
                    $stmtDel->execute([$spoolTypeId]);
                    $stmtManuf = $pdo->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
                    foreach ($validManufIds as $manufId) {
                        $stmtManuf->execute([$spoolTypeId, $manufId]);
                    }
                }
                $pdo->commit();
                echo json_encode(['success' => true, 'id' => $spoolTypeId, 'message' => 'Typ cívky byl upraven']);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }

        $stmtProposal = $pdo->prepare("SELECT 1 FROM spool_types WHERE spool_type_id = ? AND approved = 0 AND invalidated_at IS NULL LIMIT 1");
        $stmtProposal->execute([$spoolTypeId]);
        if ($stmtProposal->fetchColumn() !== false) {
            http_response_code(400);
            echo json_encode(['error' => 'Pro tento typ cívky již existuje čekající návrh na změnu.']);
            exit;
        }
        $stmt = $pdo->prepare("
            INSERT INTO spool_types (spool_type_id, weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, public, approved, created_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0, NOW(), ?)
        ");
        $stmt->execute([$spoolTypeId, $w, $c, $m, $o, $wi, $v, $userId]);
        if ($validManufIds !== null) {
            $stmtDel = $pdo->prepare("DELETE FROM spool_manufacturer WHERE spool_id = ?");
            $stmtDel->execute([$spoolTypeId]);
            $stmtManuf = $pdo->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
            foreach ($validManufIds as $manufId) {
                $stmtManuf->execute([$spoolTypeId, $manufId]);
            }
        }
        echo json_encode(['success' => true, 'id' => $spoolTypeId, 'message' => 'Návrh na změnu byl odeslán. Po schválení administrátorem se typ cívky změní.']);
        exit;
    }

    if ($createdBy !== $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'Nemáte oprávnění upravit tento typ cívky']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $stmtInv = $pdo->prepare("UPDATE spool_types SET invalidated_at = NOW(), invalidated_by = ? WHERE spool_type_id = ? AND approved = 1 AND invalidated_at IS NULL");
        $stmtInv->execute([$userId, $spoolTypeId]);
        $stmtIns = $pdo->prepare("
            INSERT INTO spool_types (spool_type_id, weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, public, approved, created_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, 1, NOW(), ?)
        ");
        $stmtIns->execute([$spoolTypeId, $w, $c, $m, $o, $wi, $v, $userId]);
        if ($validManufIds !== null) {
            $stmtDel = $pdo->prepare("DELETE FROM spool_manufacturer WHERE spool_id = ?");
            $stmtDel->execute([$spoolTypeId]);
            $stmtManuf = $pdo->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
            foreach ($validManufIds as $manufId) {
                $stmtManuf->execute([$spoolTypeId, $manufId]);
            }
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'id' => $spoolTypeId, 'message' => 'Typ cívky byl upraven']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
