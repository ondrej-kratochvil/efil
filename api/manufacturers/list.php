<?php
declare(strict_types=1);

/**
 * Seznam výrobců (detailnější než options) – pro správu výrobců.
 * GET /api/manufacturers/list.php
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

$userId = (int) $_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin_efil';

try {
    $list = getManufacturersForOptions($pdo, $userId, $isAdmin);
    echo json_encode($list);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
