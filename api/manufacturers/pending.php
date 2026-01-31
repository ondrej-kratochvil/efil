<?php
declare(strict_types=1);

/**
 * Seznam čekajících návrhů na změnu výrobců (approved=0, invalidated_at IS NULL).
 * Pouze pro admin_efil.
 * GET /api/manufacturers/pending.php
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

try {
    $stmt = $pdo->prepare("
        SELECT
            m.manufacturer_id AS id,
            m.name AS proposed_name,
            m.created_at AS proposed_at,
            m.created_by AS proposed_by_user_id,
            u.email AS proposed_by_email,
            m_approved.name AS current_approved_name
        FROM manufacturers m
        JOIN users u ON u.id = m.created_by
        LEFT JOIN manufacturers m_approved
            ON m_approved.manufacturer_id = m.manufacturer_id
            AND m_approved.approved = 1
            AND m_approved.invalidated_at IS NULL
        WHERE m.approved = 0 AND m.invalidated_at IS NULL
        ORDER BY m.created_at ASC
    ");
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($list);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
