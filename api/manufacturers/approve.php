<?php
declare(strict_types=1);

/**
 * Schválení návrhu na změnu výrobce (admin).
 * Stará schválená verze: invalidated_at = NOW(), invalidated_by = admin ID.
 * Návrh: approved = 1 (příp. název z body).
 * POST /api/manufacturers/approve.php
 * Body: { "id": manufacturer_id (logické), "name": "Finální název" (volitelně, jinak z návrhu) }
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
$manufacturerId = isset($data['id']) ? (int) $data['id'] : 0;
$finalName = isset($data['name']) ? trim((string) $data['name']) : null;

if ($manufacturerId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID výrobce je povinné']);
    exit;
}

$adminId = (int) $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    $stmtProposal = $pdo->prepare("
        SELECT id, name
        FROM manufacturers
        WHERE manufacturer_id = ? AND approved = 0 AND invalidated_at IS NULL
        LIMIT 1
    ");
    $stmtProposal->execute([$manufacturerId]);
    $proposal = $stmtProposal->fetch(PDO::FETCH_ASSOC);

    if (!$proposal) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Návrh nenalezen']);
        exit;
    }

    $nameToUse = ($finalName !== null && $finalName !== '') ? $finalName : $proposal['name'];

    $stmtInvalidate = $pdo->prepare("
        UPDATE manufacturers
        SET invalidated_at = NOW(), invalidated_by = ?
        WHERE manufacturer_id = ? AND approved = 1 AND invalidated_at IS NULL
    ");
    $stmtInvalidate->execute([$adminId, $manufacturerId]);

    $stmtApprove = $pdo->prepare("
        UPDATE manufacturers
        SET approved = 1, name = ?
        WHERE manufacturer_id = ? AND approved = 0 AND invalidated_at IS NULL
    ");
    $stmtApprove->execute([$nameToUse, $manufacturerId]);

    $pdo->commit();
    echo json_encode([
        'message' => 'Návrh byl schválen',
        'id' => $manufacturerId,
        'name' => $nameToUse,
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
