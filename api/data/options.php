<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/manufacturers.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

try {
    $userId = (int) $_SESSION['user_id'];
    $isAdmin = ($_SESSION['role'] ?? '') === 'admin_efil';

    // Aktivní evidence: vlastní nebo sdílená
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

    $materials = [];
    $manufacturers = [];
    $locations = [];
    $sellers = [];

    if ($inv) {
        $invId = (int) $inv['id'];

        // Materiály: z filamentů v evidenci (beze změny)
        $sql = "SELECT material, COUNT(*) as count FROM filaments WHERE inventory_id = ? AND material IS NOT NULL AND material != '' GROUP BY material ORDER BY count DESC, material ASC LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$invId]);
        $topMaterials = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $sql = "SELECT DISTINCT material FROM filaments WHERE inventory_id = ? AND material IS NOT NULL AND material != '' ORDER BY material";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$invId]);
        $dbMaterials = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $allMaterials = $dbMaterials;

        if (!empty($topMaterials)) {
            $othersMaterials = array_values(array_diff($allMaterials, $topMaterials));
            sort($othersMaterials);
            $materials = ['top' => $topMaterials, 'others' => $othersMaterials];
        } else {
            $materials = $allMaterials;
        }

        // Výrobci: z tabulky manufacturers (veřejní + vlastní), název = aktuální verze / návrh pro autora
        $manList = getManufacturersForOptions($pdo, $userId, $isAdmin);

        // Frekvence manufacturer_id v této evidenci (pro top/others)
        $sql = "
            SELECT manufacturer_id, COUNT(*) as cnt
            FROM filaments
            WHERE inventory_id = ? AND manufacturer_id IS NOT NULL
            GROUP BY manufacturer_id
            ORDER BY cnt DESC, manufacturer_id ASC
            LIMIT 5
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$invId]);
        $topManIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $manById = [];
        foreach ($manList as $m) {
            $manById[$m['id']] = $m;
        }
        $topManufacturers = [];
        $othersManufacturers = [];
        foreach ($topManIds as $mid) {
            if (isset($manById[$mid])) {
                $topManufacturers[] = $manById[$mid];
            }
        }
        foreach ($manList as $m) {
            if (!in_array((int) $m['id'], $topManIds, true)) {
                $othersManufacturers[] = $m;
            }
        }
        usort($othersManufacturers, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        if (!empty($topManufacturers)) {
            $manufacturers = ['top' => $topManufacturers, 'others' => $othersManufacturers];
        } else {
            $manufacturers = $manList;
        }

        // Lokace a prodejci: z filamentů (beze změny)
        $sql = "SELECT DISTINCT location FROM filaments WHERE inventory_id = ? AND location IS NOT NULL AND location != '' ORDER BY location";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$invId]);
        $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $sql = "SELECT DISTINCT seller FROM filaments WHERE inventory_id = ? AND seller IS NOT NULL AND seller != '' ORDER BY seller";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$invId]);
        $sellers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $result = [
        'materials' => is_array($materials) && isset($materials['top']) ? $materials : array_values($materials),
        'manufacturers' => is_array($manufacturers) && isset($manufacturers['top']) ? $manufacturers : array_values($manufacturers),
        'locations' => array_values($locations),
        'sellers' => array_values($sellers),
    ];

    echo json_encode($result, JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    echo json_encode([]);
}
