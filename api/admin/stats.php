<?php
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
    $db = getDBConnection();
    
    // Check if user has admin_efil role
    $stmt = $db->prepare("SELECT system_role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || $user['system_role'] !== 'admin_efil') {
        http_response_code(403);
        echo json_encode(['error' => 'Nedostatečná oprávnění']);
        exit;
    }
    
    // Get overall statistics
    $stats = [];
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total inventories
    $stmt = $db->query("SELECT COUNT(*) as count FROM inventories");
    $stats['total_inventories'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total filaments
    $stmt = $db->query("SELECT COUNT(*) as count FROM filaments");
    $stats['total_filaments'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total weight in kg
    $stmt = $db->query("SELECT COALESCE(SUM(current_weight), 0) as total FROM filaments");
    $stats['total_weight_kg'] = round($stmt->fetch(PDO::FETCH_ASSOC)['total'] / 1000, 2);
    
    // Total consumption records
    $stmt = $db->query("SELECT COUNT(*) as count FROM consumption_log");
    $stats['total_consumptions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total consumed weight in kg
    $stmt = $db->query("SELECT COALESCE(SUM(consumed_weight), 0) as total FROM consumption_log");
    $stats['total_consumed_kg'] = round($stmt->fetch(PDO::FETCH_ASSOC)['total'] / 1000, 2);
    
    // Recent registrations (last 30 days)
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['recent_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active users (logged in last 30 days)
    $stmt = $db->query("SELECT COUNT(DISTINCT user_id) as count FROM consumption_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Top inventories by filament count
    $stmt = $db->query("
        SELECT i.name, COUNT(f.id) as filament_count, 
               COALESCE(SUM(f.current_weight), 0) as total_weight
        FROM inventories i
        LEFT JOIN filaments f ON i.id = f.inventory_id
        GROUP BY i.id
        ORDER BY filament_count DESC
        LIMIT 10
    ");
    $stats['top_inventories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Material distribution
    $stmt = $db->query("
        SELECT material, COUNT(*) as count, 
               COALESCE(SUM(current_weight), 0) as total_weight
        FROM filaments
        GROUP BY material
        ORDER BY count DESC
    ");
    $stats['material_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent activity (last 20 consumption records)
    $stmt = $db->query("
        SELECT cl.id, cl.consumed_weight, cl.consumption_date, cl.note,
               f.manufacturer, f.material, f.color,
               u.email as user_email,
               i.name as inventory_name
        FROM consumption_log cl
        LEFT JOIN filaments f ON cl.filament_id = f.id
        LEFT JOIN users u ON cl.created_by = u.id
        LEFT JOIN inventories i ON f.inventory_id = i.id
        ORDER BY cl.created_at DESC
        LIMIT 20
    ");
    $stats['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
