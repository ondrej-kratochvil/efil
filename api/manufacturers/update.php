<?php
declare(strict_types=1);

/**
 * Úprava výrobce:
 * - Vlastní soukromý (public=0, created_by=userId): nová verze, stará invalidována (invalidated_by=userId).
 * - Veřejný (public=1): nelze přímo editovat; vytvoří se návrh (approved=0). Jen jeden aktivní návrh na manufacturer_id.
 * POST /api/manufacturers/update.php
 * Body: { "id": manufacturer_id (logické), "name": "Nový název" }
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
$name = isset($data['name']) ? trim((string) $data['name']) : '';

if ($manufacturerId <= 0 || $name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'ID výrobce a název jsou povinné']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin_efil';

try {
    // Aktuální schválená verze
    $stmt = $pdo->prepare("
        SELECT id, public, created_by
        FROM manufacturers
        WHERE manufacturer_id = ? AND approved = 1 AND invalidated_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([$manufacturerId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current) {
        http_response_code(404);
        echo json_encode(['error' => 'Výrobce nenalezen']);
        exit;
    }

    $isPublic = (int)($current['public'] ?? 0) === 1;
    $createdBy = (int)($current['created_by'] ?? 0);

    if ($isPublic) {
        // Administrátor upraví veřejného výrobce přímo (ihned schváleno)
        if ($isAdmin) {
            if (manufacturerNameDuplicateExists($pdo, $name, $userId, $manufacturerId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Výrobce s tímto názvem již existuje.']);
                exit;
            }
            $pdo->beginTransaction();
            try {
                $stmtInvalidate = $pdo->prepare("
                    UPDATE manufacturers
                    SET invalidated_at = NOW(), invalidated_by = ?
                    WHERE manufacturer_id = ? AND invalidated_at IS NULL
                ");
                $stmtInvalidate->execute([$userId, $manufacturerId]);

                $stmtInsert = $pdo->prepare("
                    INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_at, created_by)
                    VALUES (?, ?, 1, 1, NOW(), ?)
                ");
                $stmtInsert->execute([$manufacturerId, $name, $userId]);
                $pdo->commit();
                echo json_encode([
                    'message' => 'Výrobce byl upraven',
                    'id' => $manufacturerId,
                    'name' => $name,
                ]);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }

        // Obyčejný uživatel – veřejný výrobce pouze návrh (approved=0). Jen jeden aktivní návrh.
        $stmtProposal = $pdo->prepare("
            SELECT 1 FROM manufacturers
            WHERE manufacturer_id = ? AND approved = 0 AND invalidated_at IS NULL
            LIMIT 1
        ");
        $stmtProposal->execute([$manufacturerId]);
        if ($stmtProposal->fetchColumn() !== false) {
            http_response_code(400);
            echo json_encode(['error' => 'Pro tohoto výrobce již existuje čekající návrh na změnu.']);
            exit;
        }
        if (manufacturerNameDuplicateExists($pdo, $name, $userId, $manufacturerId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Výrobce s tímto názvem již existuje.']);
            exit;
        }
        $stmt = $pdo->prepare("
            INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_at, created_by)
            VALUES (?, ?, 1, 0, NOW(), ?)
        ");
        $stmt->execute([$manufacturerId, $name, $userId]);
        echo json_encode([
            'message' => 'Návrh na změnu byl odeslán. Po schválení administrátorem se název změní.',
            'id' => $manufacturerId,
            'name' => $name,
        ]);
        exit;
    }

    // Soukromý výrobce – jen vlastník může editovat
    if ($createdBy !== $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'Nemáte oprávnění upravit tohoto výrobce']);
        exit;
    }
    if (manufacturerNameDuplicateExists($pdo, $name, $userId, $manufacturerId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Výrobce s tímto názvem již existuje.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $stmtInvalidate = $pdo->prepare("
            UPDATE manufacturers
            SET invalidated_at = NOW(), invalidated_by = ?
            WHERE manufacturer_id = ? AND approved = 1 AND invalidated_at IS NULL
        ");
        $stmtInvalidate->execute([$userId, $manufacturerId]);

        $stmtInsert = $pdo->prepare("
            INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_at, created_by)
            VALUES (?, ?, 0, 1, NOW(), ?)
        ");
        $stmtInsert->execute([$manufacturerId, $name, $userId]);
        $pdo->commit();
        echo json_encode([
            'message' => 'Výrobce byl upraven',
            'id' => $manufacturerId,
            'name' => $name,
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
