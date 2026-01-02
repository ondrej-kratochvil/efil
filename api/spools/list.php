<?php
require_once __DIR__ . '/../../config.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

try {
    // Return all standard spools (where created_by is NULL) plus user's custom ones
    $userId = $_SESSION['user_id'];
    
    // Join with manufacturers to get name
    $sql = "
        SELECT s.id, s.weight_grams, s.color, s.material, s.outer_diameter_mm, s.width_mm, s.visual_description, 
               COALESCE(m.name, 'Neznámý') as manufacturer 
        FROM spool_library s
        LEFT JOIN manufacturers m ON s.manufacturer_id = m.id
        WHERE s.created_by IS NULL OR s.created_by = ?
        ORDER BY s.color, s.material, s.outer_diameter_mm, s.weight_grams
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $spools = $stmt->fetchAll();

    echo json_encode($spools);

} catch (Exception $e) {
    echo json_encode([]);
}
