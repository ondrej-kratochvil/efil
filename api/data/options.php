<?php
require_once __DIR__ . '/../../config.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

try {
    $userId = $_SESSION['user_id'];

    // Get active inventory: owned or shared
    $sql = "
        SELECT i.id
        FROM inventories i
        WHERE i.owner_id = ?
        UNION
        SELECT i.id
        FROM inventories i
        JOIN inventory_members im ON i.id = im.inventory_id
        WHERE im.user_id = ?
        LIMIT 1
    ";
    $stmtInv = $pdo->prepare($sql);
    $stmtInv->execute([$userId, $userId]);
    $inv = $stmtInv->fetch();

    // Initialize empty arrays - will be populated from DB only
    $materials = [];
    $manufacturers = [];
    $locations = [];
    $sellers = [];

    if ($inv) {
        // Get material frequencies (count occurrences in inventory)
        $sql = "SELECT material, COUNT(*) as count FROM filaments WHERE inventory_id = ? AND material IS NOT NULL AND material != '' GROUP BY material ORDER BY count DESC, material ASC LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$inv['id']]);
        $topMaterials = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get manufacturer frequencies (count occurrences in inventory)
        $sql = "SELECT f.manufacturer, COUNT(*) as count FROM filaments f WHERE f.inventory_id = ? AND f.manufacturer IS NOT NULL AND f.manufacturer != '' GROUP BY f.manufacturer ORDER BY count DESC, f.manufacturer ASC LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$inv['id']]);
        $topManufacturers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get all unique values from database
        $sql = "SELECT DISTINCT material FROM filaments WHERE inventory_id = ? AND material IS NOT NULL AND material != '' ORDER BY material";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$inv['id']]);
        $dbMaterials = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get manufacturers from database
        $sql = "SELECT DISTINCT name FROM manufacturers ORDER BY name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $dbManufacturers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Use only database values (no defaults)
        $allMaterials = $dbMaterials;
        $allManufacturers = $dbManufacturers;
        
        // Split into top and others for materials
        if (!empty($topMaterials)) {
            $othersMaterials = array_values(array_diff($allMaterials, $topMaterials));
            sort($othersMaterials);
            $materials = [
                'top' => $topMaterials,
                'others' => $othersMaterials
            ];
        } else {
            $materials = $allMaterials;
        }
        
        // Split into top and others for manufacturers
        if (!empty($topManufacturers)) {
            $othersManufacturers = array_values(array_diff($allManufacturers, $topManufacturers));
            sort($othersManufacturers);
            $manufacturers = [
                'top' => $topManufacturers,
                'others' => $othersManufacturers
            ];
        } else {
            $manufacturers = $allManufacturers;
        }

        $sql = "SELECT DISTINCT location FROM filaments WHERE inventory_id = ? AND location IS NOT NULL AND location != '' ORDER BY location";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$inv['id']]);
        $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $sql = "SELECT DISTINCT seller FROM filaments WHERE inventory_id = ? AND seller IS NOT NULL AND seller != '' ORDER BY seller";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$inv['id']]);
        $sellers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Ensure all arrays are properly indexed (not associative)
    $result = [
        'materials' => is_array($materials) && isset($materials['top']) ? $materials : array_values($materials),
        'manufacturers' => is_array($manufacturers) && isset($manufacturers['top']) ? $manufacturers : array_values($manufacturers),
        'locations' => array_values($locations),
        'sellers' => array_values($sellers)
    ];
    
    echo json_encode($result, JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    echo json_encode([]);
}
