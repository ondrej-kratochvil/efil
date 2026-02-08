<?php
declare(strict_types=1);

/**
 * Zamítnutí návrhu na změnu typu cívky (admin).
 * Řádek s approved=0 dostane invalidated_at = NOW(), invalidated_by = admin ID.
 * POST /api/spools/reject.php
 * Body: { "id": spool_type_id (logické) }
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
    $stmt = $pdo->prepare("
        UPDATE spool_types
        SET invalidated_at = NOW(), invalidated_by = ?
        WHERE spool_type_id = ? AND approved = 0 AND invalidated_at IS NULL
    ");
    $stmt->execute([$adminId, $spoolTypeId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Návrh nenalezen']);
        exit;
    }

    echo json_encode([
        'message' => 'Návrh byl zamítnut',
        'id' => $spoolTypeId,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
