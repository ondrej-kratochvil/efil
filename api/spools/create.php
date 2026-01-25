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
    $userId = $_SESSION['user_id'];
    
    // Begin transaction to ensure atomicity
    $pdo->beginTransaction();
    
    try {
        // Insert spool
        $stmt = $pdo->prepare("
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
        
        $spoolId = $pdo->lastInsertId();
        
        // Add manufacturer associations
        $manufData = $data['manufacturer_ids'] ?? $data['manufacturer_names'] ?? [];
        $notFoundNames = [];
        $createdAssociations = 0;
        
        if (is_array($manufData) && count($manufData) > 0) {
            // Check if we have names or IDs
            $isNames = !is_numeric($manufData[0]);
            
            if ($isNames) {
                // Resolve names to IDs
                $stmtGetId = $pdo->prepare("SELECT id FROM manufacturers WHERE name = ?");
                $stmtManuf = $pdo->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
                
                foreach ($manufData as $manufName) {
                    $stmtGetId->execute([$manufName]);
                    $manufId = $stmtGetId->fetchColumn();
                    if ($manufId) {
                        $stmtManuf->execute([$spoolId, $manufId]);
                        $createdAssociations++;
                    } else {
                        $notFoundNames[] = $manufName;
                    }
                }
                
                // If any names were not found, rollback and return error
                if (count($notFoundNames) > 0) {
                    $pdo->rollBack();
                    http_response_code(400);
                    echo json_encode([
                        'error' => 'Některé výrobce nebyly nalezeny',
                        'not_found' => $notFoundNames,
                        'created_associations' => $createdAssociations
                    ]);
                    exit;
                }
            } else {
                // Use IDs directly
                $stmtManuf = $pdo->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
                foreach ($manufData as $manufId) {
                    $stmtManuf->execute([$spoolId, $manufId]);
                }
            }
        }
        
        // Commit transaction if everything succeeded
        $pdo->commit();
        echo json_encode(['success' => true, 'id' => $spoolId]);
        
    } catch (Exception $e) {
        // Rollback on any error within transaction
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
