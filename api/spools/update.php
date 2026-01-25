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

    if ($spool['created_by'] != $userId) {
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

    if (count($updates) > 0) {
        $params[] = $spoolId;
        $sql = "UPDATE spool_library SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    // Update manufacturer associations
    $manufData = $data['manufacturer_ids'] ?? $data['manufacturer_names'] ?? null;
    $notFoundNames = [];
    $createdAssociations = 0;
    
    if ($manufData !== null) {
        // Delete existing associations
        $stmt = $pdo->prepare("DELETE FROM spool_manufacturer WHERE spool_id = ?");
        $stmt->execute([$spoolId]);

        // Add new associations
        if (is_array($manufData) && count($manufData) > 0) {
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
                
                // If any names were not found, return error
                if (count($notFoundNames) > 0) {
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
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
