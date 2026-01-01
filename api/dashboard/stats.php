<?php
require_once __DIR__ . '/../../config.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // 1. Get Inventory ID
    $stmtInv = $pdo->prepare("SELECT id FROM inventories WHERE owner_id = ? LIMIT 1");
    $stmtInv->execute([$userId]);
    $inv = $stmtInv->fetch();
    
    if (!$inv) { echo json_encode([]); exit; }
    $invId = $inv['id'];

    // 2. Total Weight & Value Calculation
    // We need to fetch individual filaments to calculate value proportional to remaining weight
    $sql = "
        SELECT 
            f.price,
            f.initial_weight_grams,
            (f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0)) as current_weight
        FROM filaments f
        LEFT JOIN consumption_log cl ON f.id = cl.filament_id
        WHERE f.inventory_id = ?
        GROUP BY f.id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$invId]);
    $rows = $stmt->fetchAll();

    $totalWeight = 0;
    $totalValue = 0;
    $totalCount = count($rows);

    foreach ($rows as $row) {
        $w = (int)$row['current_weight'];
        $initW = (int)$row['initial_weight_grams'];
        $price = (float)$row['price'];

        if ($w > 0) {
            $totalWeight += $w;
            // Calculate proportional value if price and initial weight are known
            if ($price > 0 && $initW > 0) {
                $ratio = $w / $initW;
                $totalValue += ($price * $ratio);
            }
        }
    }

    // 3. Consumption Stats (Last 30 days)
    // We sum negative values from consumption_log (excluding corrections if possible, but schema doesn't distinguish explicitly other than sign)
    // Usually positive corrections are small, but let's just sum negative amounts.
    $sql = "
        SELECT SUM(cl.amount_grams) as consumed
        FROM consumption_log cl
        JOIN filaments f ON cl.filament_id = f.id
        WHERE f.inventory_id = ? 
          AND cl.amount_grams < 0
          AND cl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$invId]);
    $consumed30 = abs((int)$stmt->fetchColumn());

    echo json_encode([
        'total_weight_grams' => $totalWeight,
        'total_value_czk' => round($totalValue),
        'total_count' => $totalCount,
        'consumed_30_days_grams' => $consumed30
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
