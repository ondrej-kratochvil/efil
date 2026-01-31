<?php
declare(strict_types=1);

/**
 * Vytvoření nového soukromého výrobce (public=0, approved=1).
 * POST /api/manufacturers/create.php
 * Body: { "name": "Název výrobce" }
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
$name = isset($data['name']) ? trim((string) $data['name']) : '';

if ($name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Název výrobce je povinný']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

try {
    if (manufacturerNameDuplicateExists($pdo, $name, $userId, null)) {
        http_response_code(400);
        echo json_encode(['error' => 'Výrobce s tímto názvem již existuje (veřejný nebo ve vašem seznamu).']);
        exit;
    }

    $nextId = getNextManufacturerId($pdo);
    $stmt = $pdo->prepare("
        INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_at, created_by)
        VALUES (?, ?, 0, 1, NOW(), ?)
    ");
    $stmt->execute([$nextId, $name, $userId]);

    echo json_encode([
        'message' => 'Výrobce byl vytvořen',
        'id' => $nextId,
        'name' => $name,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
