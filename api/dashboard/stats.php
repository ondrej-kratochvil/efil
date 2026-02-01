<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/inventory.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$userId = (int) $_SESSION['user_id'];

try {
    $inventoryId = getInventoryIdForUser($pdo, $userId, true);
    if ($inventoryId === null) {
        echo json_encode([
            'total_weight_grams' => 0,
            'total_value_czk' => 0,
            'total_count' => 0,
            'consumed_30_days_grams' => 0,
            'material_distribution' => [],
            'consumption_by_day' => [],
        ]);
        exit;
    }

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
    $stmt->execute([$inventoryId]);
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
    $stmt->execute([$inventoryId]);
    $consumed30 = abs((int)$stmt->fetchColumn());

    // 4. Material distribution (zbývající hmotnost) – pro koláčový graf
    $stmt = $pdo->prepare("
        SELECT f.material,
               COALESCE(SUM(f.initial_weight_grams + COALESCE(consumption_sum.total_consumed, 0)), 0) AS remaining_weight
        FROM filaments f
        LEFT JOIN (
            SELECT filament_id, SUM(amount_grams) AS total_consumed
            FROM consumption_log
            GROUP BY filament_id
        ) consumption_sum ON f.id = consumption_sum.filament_id
        WHERE f.inventory_id = ?
        GROUP BY f.material
        HAVING remaining_weight > 0
        ORDER BY remaining_weight DESC
    ");
    $stmt->execute([$inventoryId]);
    $materialDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Spotřeba po dnech (posledních 30 dní) – pro sloupcový graf
    $stmt = $pdo->prepare("
        SELECT cl.consumption_date AS date, COALESCE(SUM(ABS(cl.amount_grams)), 0) AS total_grams
        FROM consumption_log cl
        JOIN filaments f ON cl.filament_id = f.id
        WHERE f.inventory_id = ? AND cl.amount_grams < 0
          AND cl.consumption_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY cl.consumption_date
        ORDER BY cl.consumption_date ASC
    ");
    $stmt->execute([$inventoryId]);
    $consumptionByDay = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'total_weight_grams' => $totalWeight,
        'total_value_czk' => round($totalValue),
        'total_count' => $totalCount,
        'consumed_30_days_grams' => $consumed30,
        'material_distribution' => $materialDistribution,
        'consumption_by_day' => $consumptionByDay,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
