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

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing spool ID']);
    exit;
}

try {
    $db = getDBConnection();
    $userId = $_SESSION['user_id'];
    $spoolId = $data['id'];
    
    // Verify user can edit this spool (must be creator or it must be a standard spool)
    $stmt = $db->prepare("SELECT created_by FROM spool_library WHERE id = ?");
    $stmt->execute([$spoolId]);
    $spool = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$spool) {
        http_response_code(404);
        echo json_encode(['error' => 'Typ cívky nenalezen']);
        exit;
    }
    
    if ($spool['created_by'] !== null && $spool['created_by'] != $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'Nemáte oprávnění upravovat tento typ cívky']);
        exit;
    }
    
    // Update spool
    $stmt = $db->prepare("
        UPDATE spool_library 
        SET weight_grams = ?, color = ?, material = ?, outer_diameter_mm = ?, width_mm = ?, visual_description = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $data['weight_grams'] ?? null,
        $data['color'] ?? null,
        $data['material'] ?? null,
        $data['outer_diameter_mm'] ?? null,
        $data['width_mm'] ?? null,
        $data['visual_description'] ?? null,
        $spoolId
    ]);
    
    // Update manufacturer associations
    $manufData = $data['manufacturer_ids'] ?? $data['manufacturer_names'] ?? null;
    if ($manufData !== null) {
        // Delete existing associations
        $stmt = $db->prepare("DELETE FROM spool_manufacturer WHERE spool_id = ?");
        $stmt->execute([$spoolId]);
        
        // Add new associations
        if (is_array($manufData) && count($manufData) > 0) {
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
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
