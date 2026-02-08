<?php
declare(strict_types=1);

/**
 * Soft delete výrobce: není-li použit u filamentu ani u typu cívky,
 * aktuální platné verze (approved=1 a případně approved=0) dostanou
 * invalidated_at = NOW() a invalidated_by = ID přihlášeného uživatele.
 * POST /api/manufacturers/delete.php
 * Body: { "id": manufacturer_id (logické) }
 */

require_once __DIR__ . '/../../config.php';
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
$manufacturerId = isset($data['id']) ? (int) $data['id'] : 0;

if ($manufacturerId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID výrobce je povinné']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

try {
    if (isManufacturerInUse($pdo, $manufacturerId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Výrobce je použit u filamentu nebo typu cívky. Nelze smazat.']);
        exit;
    }

    // Oprávnění: smazat může vlastník soukromého výrobce, nebo admin u kohokoli
    $stmt = $pdo->prepare("
        SELECT id, public, created_by
        FROM manufacturers
        WHERE manufacturer_id = ? AND approved = 1 AND invalidated_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([$manufacturerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Výrobce nenalezen']);
        exit;
    }

    $isAdmin = ($_SESSION['role'] ?? '') === 'admin_efil';
    $isPublic = (int)($row['public'] ?? 0) === 1;
    $createdBy = (int)($row['created_by'] ?? 0);

    if (!$isAdmin && ($isPublic || $createdBy !== $userId)) {
        http_response_code(403);
        echo json_encode(['error' => 'Nemáte oprávnění smazat tohoto výrobce']);
        exit;
    }

    // Zneplatnit všechny aktuálně platné verze (schválenou i případný návrh); invalidated_by = kdo smazal
    $stmtUpdate = $pdo->prepare("
        UPDATE manufacturers
        SET invalidated_at = NOW(), invalidated_by = ?
        WHERE manufacturer_id = ? AND invalidated_at IS NULL
    ");
    $stmtUpdate->execute([$userId, $manufacturerId]);

    echo json_encode([
        'message' => 'Výrobce byl smazán',
        'id' => $manufacturerId,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
