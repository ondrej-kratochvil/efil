<?php
declare(strict_types=1);

/**
 * Zamítnutí návrhu na změnu výrobce (admin).
 * Řádek s approved=0 dostane invalidated_at = NOW(), invalidated_by = admin ID.
 * POST /api/manufacturers/reject.php
 * Body: { "id": manufacturer_id (logické) }
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

if ($manufacturerId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID výrobce je povinné']);
    exit;
}

$adminId = (int) $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        UPDATE manufacturers
        SET invalidated_at = NOW(), invalidated_by = ?
        WHERE manufacturer_id = ? AND approved = 0 AND invalidated_at IS NULL
    ");
    $stmt->execute([$adminId, $manufacturerId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Návrh nenalezen']);
        exit;
    }

    echo json_encode([
        'message' => 'Návrh byl zamítnut',
        'id' => $manufacturerId,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
