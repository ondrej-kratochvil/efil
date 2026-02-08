<?php
declare(strict_types=1);

/**
 * Schválení návrhu na změnu typu cívky (admin).
 * Stará schválená verze: invalidated_at = NOW(), invalidated_by = admin ID.
 * Návrh: approved = 1 (příp. úpravy z body).
 * POST /api/spools/approve.php
 * Body: { "id": spool_type_id (logické), volitelně weight_grams, color, material, ... }
 */

require_once __DIR__ . '/../../config.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nepřihlášen']);
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin_efil') {
    http_response_code(403);
    echo json_encode(['error' => 'Pouze pro administrátora']);
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

$adminId = (int) $_SESSION['user_id'];

try {
    $stmtProposal = $pdo->prepare("
        SELECT id, weight_grams, color, material, outer_diameter_mm, width_mm, visual_description
        FROM spool_types
        WHERE spool_type_id = ? AND approved = 0 AND invalidated_at IS NULL
        LIMIT 1
    ");
    $stmtProposal->execute([$spoolTypeId]);
    $proposal = $stmtProposal->fetch(PDO::FETCH_ASSOC);

    if (!$proposal) {
        http_response_code(404);
        echo json_encode(['error' => 'Návrh nenalezen']);
        exit;
    }

    $w = array_key_exists('weight_grams', $data) ? ($data['weight_grams'] !== null && $data['weight_grams'] !== '' ? (int) $data['weight_grams'] : null) : $proposal['weight_grams'];
    $c = array_key_exists('color', $data) ? ($data['color'] === '' ? null : trim((string) $data['color'])) : $proposal['color'];
    $m = array_key_exists('material', $data) ? ($data['material'] === '' ? null : trim((string) $data['material'])) : $proposal['material'];
    $o = array_key_exists('outer_diameter_mm', $data) ? ($data['outer_diameter_mm'] !== null && $data['outer_diameter_mm'] !== '' ? (int) $data['outer_diameter_mm'] : null) : $proposal['outer_diameter_mm'];
    $wi = array_key_exists('width_mm', $data) ? ($data['width_mm'] !== null && $data['width_mm'] !== '' ? (int) $data['width_mm'] : null) : $proposal['width_mm'];
    $v = array_key_exists('visual_description', $data) ? ($data['visual_description'] === '' ? null : trim((string) $data['visual_description'])) : $proposal['visual_description'];

    $pdo->beginTransaction();

    $stmtInvalidate = $pdo->prepare("
        UPDATE spool_types
        SET invalidated_at = NOW(), invalidated_by = ?
        WHERE spool_type_id = ? AND approved = 1 AND invalidated_at IS NULL
    ");
    $stmtInvalidate->execute([$adminId, $spoolTypeId]);

    $stmtApprove = $pdo->prepare("
        UPDATE spool_types
        SET approved = 1, weight_grams = ?, color = ?, material = ?, outer_diameter_mm = ?, width_mm = ?, visual_description = ?
        WHERE spool_type_id = ? AND approved = 0 AND invalidated_at IS NULL
    ");
    $stmtApprove->execute([$w, $c, $m, $o, $wi, $v, $spoolTypeId]);

    $pdo->commit();
    echo json_encode([
        'message' => 'Návrh byl schválen',
        'id' => $spoolTypeId,
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
