<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
session_start();

// Check if user is logged in and is admin_efil
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {

    // Check if user has admin_efil role
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin_efil') {
        http_response_code(403);
        echo json_encode(['error' => 'Nedostatečná oprávnění']);
        exit;
    }

    // Get overall statistics
    $stats = [];

    // Total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total inventories
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM inventories");
    $stmt->execute();
    $stats['total_inventories'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total filaments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM filaments");
    $stmt->execute();
    $stats['total_filaments'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total weight in kg (calculated dynamically)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(f.initial_weight_grams + COALESCE(consumption_sum.total_consumed, 0)), 0) as total
        FROM filaments f
        LEFT JOIN (
            SELECT filament_id, SUM(amount_grams) as total_consumed
            FROM consumption_log
            GROUP BY filament_id
        ) consumption_sum ON f.id = consumption_sum.filament_id
    ");
    $stmt->execute();
    $stats['total_weight_kg'] = round($stmt->fetch(PDO::FETCH_ASSOC)['total'] / 1000, 2);

    // Total consumption records
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM consumption_log");
    $stmt->execute();
    $stats['total_consumptions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total consumed weight in kg (only actual consumption: amount_grams < 0; positive = corrections)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(ABS(amount_grams)), 0) as total FROM consumption_log WHERE amount_grams < 0");
    $stmt->execute();
    $stats['total_consumed_kg'] = round($stmt->fetch(PDO::FETCH_ASSOC)['total'] / 1000, 2);

    // Recent registrations (last 30 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $stats['recent_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Active users (consumption in last 30 days)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT created_by) as count FROM consumption_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Top inventories by filament count
    $stmt = $pdo->prepare("
        SELECT i.name, COUNT(f.id) as filament_count,
               COALESCE(SUM(f.initial_weight_grams + COALESCE(consumption_sum.total_consumed, 0)), 0) as total_weight
        FROM inventories i
        LEFT JOIN filaments f ON i.id = f.inventory_id
        LEFT JOIN (
            SELECT filament_id, SUM(amount_grams) as total_consumed
            FROM consumption_log
            GROUP BY filament_id
        ) consumption_sum ON f.id = consumption_sum.filament_id
        GROUP BY i.id
        ORDER BY filament_count DESC
        LIMIT 10
    ");
    $stmt->execute();
    $stats['top_inventories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Material distribution
    $stmt = $pdo->prepare("
        SELECT f.material, COUNT(*) as count,
               COALESCE(SUM(f.initial_weight_grams + COALESCE(consumption_sum.total_consumed, 0)), 0) as total_weight
        FROM filaments f
        LEFT JOIN (
            SELECT filament_id, SUM(amount_grams) as total_consumed
            FROM consumption_log
            GROUP BY filament_id
        ) consumption_sum ON f.id = consumption_sum.filament_id
        GROUP BY f.material
        ORDER BY count DESC
    ");
    $stmt->execute();
    $stats['material_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent activity (last 20 consumption records) – název výrobce stejně jako jinde: COALESCE(návrh přihlášeného, schválený)
    $adminId = (int) $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        SELECT cl.id, ABS(cl.amount_grams) as consumed_weight, cl.consumption_date, cl.description as note,
               COALESCE(m_proposal.name, m_approved.name) AS manufacturer, f.material, f.color_name as color,
               u.email as user_email,
               i.name as inventory_name
        FROM consumption_log cl
        LEFT JOIN filaments f ON cl.filament_id = f.id
        LEFT JOIN (SELECT manufacturer_id, MAX(id) AS mid FROM manufacturers WHERE approved = 1 AND invalidated_at IS NULL GROUP BY manufacturer_id) m_approved_ids ON f.manufacturer_id = m_approved_ids.manufacturer_id
        LEFT JOIN manufacturers m_approved ON m_approved.id = m_approved_ids.mid AND m_approved.manufacturer_id = m_approved_ids.manufacturer_id
        LEFT JOIN (SELECT manufacturer_id, created_by, MAX(id) AS mid FROM manufacturers WHERE approved = 0 AND invalidated_at IS NULL GROUP BY manufacturer_id, created_by) m_proposal_ids ON f.manufacturer_id = m_proposal_ids.manufacturer_id AND m_proposal_ids.created_by = ?
        LEFT JOIN manufacturers m_proposal ON m_proposal.id = m_proposal_ids.mid AND m_proposal.manufacturer_id = m_proposal_ids.manufacturer_id AND m_proposal.created_by = m_proposal_ids.created_by
        LEFT JOIN users u ON cl.created_by = u.id
        LEFT JOIN inventories i ON f.inventory_id = i.id
        ORDER BY cl.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$adminId]);
    $stats['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($stats);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
