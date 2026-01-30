<?php
declare(strict_types=1);

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
    $userId = $_SESSION['user_id'];
    $spoolId = $data['id'];

    // Verify user can edit this spool (must be creator or it must be a standard spool)
    $stmt = $pdo->prepare("SELECT created_by FROM spool_library WHERE id = ?");
    $stmt->execute([$spoolId]);
    $spool = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$spool) {
        http_response_code(404);
        echo json_encode(['error' => 'Typ cívky nenalezen']);
        exit;
    }

    if ($spool['created_by'] === null) {
        http_response_code(403);
        echo json_encode(['error' => 'Nelze upravit standardní typ cívky']);
        exit;
    }

    if ((int) $spool['created_by'] !== (int) $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'Nemáte oprávnění upravovat tento typ cívky']);
        exit;
    }

    // Update spool – only fields explicitly provided (partial updates supported)
    $updates = [];
    $params = [];

    if (array_key_exists('weight_grams', $data)) {
        $updates[] = "weight_grams = ?";
        $params[] = $data['weight_grams'] !== null && $data['weight_grams'] !== '' ? (int) $data['weight_grams'] : null;
    }
    if (array_key_exists('color', $data)) {
        $updates[] = "color = ?";
        $params[] = $data['color'] === '' ? null : ($data['color'] ?? null);
    }
    if (array_key_exists('material', $data)) {
        $updates[] = "material = ?";
        $params[] = $data['material'] === '' ? null : ($data['material'] ?? null);
    }
    if (array_key_exists('outer_diameter_mm', $data)) {
        $updates[] = "outer_diameter_mm = ?";
        $params[] = $data['outer_diameter_mm'] !== null && $data['outer_diameter_mm'] !== '' ? (int) $data['outer_diameter_mm'] : null;
    }
    if (array_key_exists('width_mm', $data)) {
        $updates[] = "width_mm = ?";
        $params[] = $data['width_mm'] !== null && $data['width_mm'] !== '' ? (int) $data['width_mm'] : null;
    }
    if (array_key_exists('visual_description', $data)) {
        $updates[] = "visual_description = ?";
        $params[] = $data['visual_description'] === '' ? null : ($data['visual_description'] ?? null);
    }

    // Check if manufacturer associations are being updated
    $manufData = $data['manufacturer_ids'] ?? $data['manufacturer_names'] ?? null;
    $hasManufUpdate = $manufData !== null;
    $hasSpoolUpdate = count($updates) > 0;
    
    // Use transaction whenever multiple operations run: spool UPDATE and/or manufacturer DELETE+INSERT.
    // Required when only manufacturers are updated so that failed INSERT after DELETE can roll back.
    $useTransaction = $hasSpoolUpdate || $hasManufUpdate;
    
    if ($useTransaction) {
        $pdo->beginTransaction();
    }
    
    try {
        // Update spool data (if any fields provided)
        if ($hasSpoolUpdate) {
            $params[] = $spoolId;
            $sql = "UPDATE spool_library SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
        
        // Update manufacturer associations (if provided)
        if ($hasManufUpdate) {
            // First, validate all manufacturer names (if using names) BEFORE deleting anything
            $notFoundNames = [];
            $manufacturerIds = [];
            
            if (is_array($manufData) && count($manufData) > 0) {
                $isNames = !is_numeric($manufData[0]);

                if ($isNames) {
                    // Validate all names first - resolve to IDs before any deletion
                    $stmtGetId = $pdo->prepare("SELECT id FROM manufacturers WHERE name = ?");
                    
                    foreach ($manufData as $manufName) {
                        $stmtGetId->execute([$manufName]);
                        $manufId = $stmtGetId->fetchColumn();
                        if ($manufId) {
                            $manufacturerIds[] = $manufId;
                        } else {
                            $notFoundNames[] = $manufName;
                        }
                    }
                    
                    // If any names were not found, rollback and return error
                    if (count($notFoundNames) > 0) {
                        if ($useTransaction) {
                            $pdo->rollBack();
                        }
                        http_response_code(400);
                        echo json_encode([
                            'error' => 'Některé výrobce nebyly nalezeny',
                            'not_found' => $notFoundNames
                        ]);
                        exit;
                    }
                } else {
                    // Use IDs directly - validate they exist
                    $placeholders = implode(',', array_fill(0, count($manufData), '?'));
                    $stmtValidate = $pdo->prepare("SELECT id FROM manufacturers WHERE id IN ($placeholders)");
                    $stmtValidate->execute($manufData);
                    $validIds = $stmtValidate->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (count($validIds) !== count($manufData)) {
                        if ($useTransaction) {
                            $pdo->rollBack();
                        }
                        http_response_code(400);
                        echo json_encode(['error' => 'Některé ID výrobců nejsou platné']);
                        exit;
                    }
                    
                    $manufacturerIds = $manufData;
                }
            }
            
            // Now that validation passed, delete existing associations
            $stmt = $pdo->prepare("DELETE FROM spool_manufacturer WHERE spool_id = ?");
            $stmt->execute([$spoolId]);

            // Add new associations
            if (count($manufacturerIds) > 0) {
                $stmtManuf = $pdo->prepare("INSERT INTO spool_manufacturer (spool_id, manufacturer_id) VALUES (?, ?)");
                foreach ($manufacturerIds as $manufId) {
                    $stmtManuf->execute([$spoolId, $manufId]);
                }
            }
        }
        
        // Commit transaction if one was started
        if ($useTransaction) {
            $pdo->commit();
        }
        
    } catch (Exception $e) {
        // Rollback on any error if transaction was started
        if ($useTransaction) {
            $pdo->rollBack();
        }
        throw $e;
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
