<?php
declare(strict_types=1);

/**
 * Seznam čekajících návrhů na změnu typů cívek (approved=0, invalidated_at IS NULL).
 * Pouze pro admin_efil.
 * GET /api/spools/pending.php
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/spool_types.php';

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
            st.spool_type_id AS id,
            st.weight_grams,
            st.color,
            st.material,
            st.outer_diameter_mm,
            st.width_mm,
            st.visual_description,
            st.created_at AS proposed_at,
            st.created_by AS proposed_by_user_id,
            u.email AS proposed_by_email
        FROM spool_types st
        JOIN users u ON u.id = st.created_by
        WHERE st.approved = 0 AND st.invalidated_at IS NULL
        ORDER BY st.created_at ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $list = [];
    foreach ($rows as $row) {
        $stId = (int) $row['id'];
        $currentApproved = $pdo->prepare("
            SELECT weight_grams, color, material, outer_diameter_mm, width_mm, visual_description
            FROM spool_types
            WHERE spool_type_id = ? AND approved = 1 AND invalidated_at IS NULL
            LIMIT 1
        ");
        $currentApproved->execute([$stId]);
        $approved = $currentApproved->fetch(PDO::FETCH_ASSOC);
        $list[] = [
            'id' => $stId,
            'proposed' => [
                'weight_grams' => $row['weight_grams'],
                'color' => $row['color'],
                'material' => $row['material'],
                'outer_diameter_mm' => $row['outer_diameter_mm'],
                'width_mm' => $row['width_mm'],
                'visual_description' => $row['visual_description'],
                'label' => spoolTypeRowToLabel($row),
            ],
            'current_approved' => $approved !== false ? [
                'weight_grams' => $approved['weight_grams'],
                'color' => $approved['color'],
                'material' => $approved['material'],
                'outer_diameter_mm' => $approved['outer_diameter_mm'],
                'width_mm' => $approved['width_mm'],
                'visual_description' => $approved['visual_description'],
                'label' => spoolTypeRowToLabel($approved),
            ] : null,
            'proposed_at' => $row['proposed_at'],
            'proposed_by_user_id' => (int) $row['proposed_by_user_id'],
            'proposed_by_email' => $row['proposed_by_email'],
        ];
    }

    echo json_encode($list);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze']);
}
