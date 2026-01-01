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
    $stmtInv = $pdo->prepare("SELECT id FROM inventories WHERE owner_id = ? LIMIT 1");
    $stmtInv->execute([$userId]);
    $inv = $stmtInv->fetch();
    
    if (!$inv) { echo json_encode([]); exit; }

    // Get unique values for Smart Selects
    // We want all unique materials, manufacturers, locations, sellers used by this user
    $sql = "SELECT DISTINCT material FROM filaments WHERE inventory_id = ? ORDER BY material";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$inv['id']]);
    $materials = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $sql = "SELECT DISTINCT manufacturer FROM filaments WHERE inventory_id = ? ORDER BY manufacturer";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$inv['id']]);
    $manufacturers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $sql = "SELECT DISTINCT location FROM filaments WHERE inventory_id = ? AND location IS NOT NULL AND location != '' ORDER BY location";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$inv['id']]);
    $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $sql = "SELECT DISTINCT seller FROM filaments WHERE inventory_id = ? AND seller IS NOT NULL AND seller != '' ORDER BY seller";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$inv['id']]);
    $sellers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'materials' => $materials,
        'manufacturers' => $manufacturers,
        'locations' => $locations,
        'sellers' => $sellers
    ]);

} catch (Exception $e) {
    echo json_encode([]);
}
