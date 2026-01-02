<?php
require_once __DIR__ . '/../../config.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];

$manufacturerName = $input['manufacturer'] ?? null;
$weightGrams = !empty($input['weight_grams']) ? (int)$input['weight_grams'] : null;
$color = $input['color'] ?? '';
$material = $input['material'] ?? '';
$outerDiameter = !empty($input['outer_diameter_mm']) ? (int)$input['outer_diameter_mm'] : null;
$width = !empty($input['width_mm']) ? (int)$input['width_mm'] : null;
$description = $input['visual_description'] ?? '';

// At least one identifying field should be provided
if (!$color && !$material && !$outerDiameter && !$width && !$description) {
    http_response_code(400);
    echo json_encode(['error' => 'At least one identifying field (color, material, diameter, width, description) is required']);
    exit;
}

try {
    $manufacturerId = null;
    if ($manufacturerName) {
        // Find or create manufacturer
        $stmt = $pdo->prepare("SELECT id FROM manufacturers WHERE name = ?");
        $stmt->execute([$manufacturerName]);
        $manufacturer = $stmt->fetch();
        
        if (!$manufacturer) {
            // Create new manufacturer
            $stmt = $pdo->prepare("INSERT INTO manufacturers (name) VALUES (?)");
            $stmt->execute([$manufacturerName]);
            $manufacturerId = $pdo->lastInsertId();
        } else {
            $manufacturerId = $manufacturer['id'];
        }
    }
    
    // Check if spool already exists (match by characteristics)
    $sql = "
        SELECT id FROM spool_library 
        WHERE (manufacturer_id = ? OR (manufacturer_id IS NULL AND ? IS NULL))
        AND (weight_grams = ? OR (weight_grams IS NULL AND ? IS NULL))
        AND (color = ? OR (color IS NULL AND ? = ''))
        AND (material = ? OR (material IS NULL AND ? = ''))
        AND (outer_diameter_mm = ? OR (outer_diameter_mm IS NULL AND ? IS NULL))
        AND (width_mm = ? OR (width_mm IS NULL AND ? IS NULL))
        AND (visual_description = ? OR (visual_description IS NULL AND ? = ''))
        AND (created_by = ? OR created_by IS NULL)
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $manufacturerId, $manufacturerId,
        $weightGrams, $weightGrams,
        $color, $color,
        $material, $material,
        $outerDiameter, $outerDiameter,
        $width, $width,
        $description, $description,
        $userId
    ]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo json_encode(['id' => $existing['id'], 'message' => 'Spool already exists']);
        exit;
    }
    
    // Create new spool
    $stmt = $pdo->prepare("
        INSERT INTO spool_library (manufacturer_id, weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $manufacturerId,
        $weightGrams,
        $color ?: null,
        $material ?: null,
        $outerDiameter,
        $width,
        $description ?: null,
        $userId
    ]);
    
    $spoolId = $pdo->lastInsertId();
    
    // Return spool
    $stmt = $pdo->prepare("
        SELECT s.id, s.weight_grams, s.color, s.material, s.outer_diameter_mm, s.width_mm, s.visual_description, 
               COALESCE(m.name, 'Neznámý') as manufacturer 
        FROM spool_library s
        LEFT JOIN manufacturers m ON s.manufacturer_id = m.id
        WHERE s.id = ?
    ");
    $stmt->execute([$spoolId]);
    $spool = $stmt->fetch();
    
    echo json_encode($spool);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

