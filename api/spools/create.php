<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $db = getDBConnection();
    $userId = $_SESSION['user_id'];
    
    // Insert spool
    $stmt = $db->prepare("
        INSERT INTO spool_library (weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['weight_grams'] ?? null,
        $data['color'] ?? null,
        $data['material'] ?? null,
        $data['outer_diameter_mm'] ?? null,
        $data['width_mm'] ?? null,
        $data['visual_description'] ?? null,
        $userId
    ]);
    
    $spoolId = $db->lastInsertId();
    
    // Add manufacturer associations
    $manufData = $data['manufacturer_ids'] ?? $data['manufacturer_names'] ?? [];
    if (is_array($manufData) && count($manufData) > 0) {
        // Check if we have names or IDs
        $isNames = !is_numeric($manufData[0]);
        
        if ($isNames) {
            // Resolve names to IDs
            $stmtGetId = $db->prepare("SELECT id FROM manufacturers WHERE name = ?");
            $stmtManuf = $db->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
            
            foreach ($manufData as $manufName) {
                $stmtGetId->execute([$manufName]);
                $manufId = $stmtGetId->fetchColumn();
                if ($manufId) {
                    $stmtManuf->execute([$spoolId, $manufId]);
                }
            }
        } else {
            // Use IDs directly
            $stmtManuf = $db->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
            foreach ($manufData as $manufId) {
                $stmtManuf->execute([$spoolId, $manufId]);
            }
        }
    }
    
    echo json_encode(['success' => true, 'id' => $spoolId]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
