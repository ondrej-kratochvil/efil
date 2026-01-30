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
    echo json_encode(['error' => 'Missing consumption ID']);
    exit;
}

try {
    $consumptionId = $data['id'];
    $userId = $_SESSION['user_id'];
    
    // Get inventory_id from session, or get first available inventory
    $inventoryId = $_SESSION['inventory_id'] ?? null;
    
    if (!$inventoryId) {
        // Get first available inventory for user
        $stmtInv = $pdo->prepare("
            SELECT i.id
            FROM inventories i
            WHERE i.owner_id = ?
            UNION
            SELECT i.id
            FROM inventories i
            JOIN inventory_members im ON i.id = im.inventory_id
            WHERE im.user_id = ?
            LIMIT 1
        ");
        $stmtInv->execute([$userId, $userId]);
        $inv = $stmtInv->fetch(PDO::FETCH_ASSOC);
        
        if (!$inv) {
            http_response_code(404);
            echo json_encode(['error' => 'No inventory found']);
            exit;
        }
        
        $inventoryId = $inv['id'];
    }

    // Verify access - user must have write/manage permission to the inventory
    // Check if user is owner OR has write/manage role in inventory_members
    $stmt = $pdo->prepare("
        SELECT cl.id, cl.amount_grams as old_weight, cl.filament_id, i.is_demo
        FROM consumption_log cl
        INNER JOIN filaments f ON cl.filament_id = f.id
        INNER JOIN inventories i ON f.inventory_id = i.id
        WHERE cl.id = ? AND i.id = ? AND (
            i.owner_id = ?
            OR EXISTS (
                SELECT 1 FROM inventory_members im
                WHERE im.inventory_id = i.id
                AND im.user_id = ?
                AND im.role IN ('write', 'manage')
            )
        )
    ");
    $stmt->execute([$consumptionId, $inventoryId, $userId, $userId]);
    $consumption = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$consumption) {
        http_response_code(403);
        echo json_encode(['error' => 'Nemáte oprávnění k tomuto záznamu']);
        exit;
    }

    // Check if user is admin_efil
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($user && $user['role'] === 'admin_efil');

    // Check if demo mode (and user is not admin)
    // MySQL TINYINT(1) may be returned as int or string; use int comparison to avoid (bool)'0' quirks
    $isDemo = ((int)($consumption['is_demo'] ?? 0) === 1);
    if ($isDemo && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'V demo režimu nelze upravovat data']);
        exit;
    }

    // Update consumption record
    $updates = [];
    $params = [];

    if (isset($data['consumed_weight'])) {
        $newWeight = (int) $data['consumed_weight'];

        // Reject zero and negative weight (consistent with filaments/consume.php).
        // Zero would void a consumption record without deleting it; negative would flip type via sign logic.
        if ($newWeight <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Hmotnost musí být větší než nula. Pro zrušení záznamu použijte smazání.']);
            exit;
        }

        // Check if we're updating a correction (positive) or consumption (negative)
        // If amount_grams is provided with sign, use it directly; otherwise use consumed_weight
        if (isset($data['amount_grams'])) {
            // Frontend sent the full amount_grams value (can be positive or negative)
            $amountGrams = (int) $data['amount_grams'];
            $originalAmount = (int) ($consumption['old_weight'] ?? 0);
            // Reject zero (would void the record)
            if ($amountGrams === 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Hmotnost musí být větší než nula. Pro zrušení záznamu použijte smazání.']);
                exit;
            }
            // Reject sign flip: consumption type must stay the same (positive = correction, negative = consumption)
            if (($originalAmount >= 0 && $amountGrams < 0) || ($originalAmount < 0 && $amountGrams > 0)) {
                http_response_code(400);
                echo json_encode(['error' => 'Nelze změnit typ záznamu (čerpání / korekce). Upravte pouze hodnotu.']);
                exit;
            }
        } else {
            // Legacy: if only consumed_weight is provided, check original value to preserve sign
            // If original was positive (correction), keep it positive; if negative (consumption), keep negative
            $originalAmount = (int) ($consumption['old_weight'] ?? 0);
            if ($originalAmount > 0) {
                // Original was a correction, so new value should also be positive
                $amountGrams = $newWeight;
            } else {
                // Original was consumption, so new value should be negative
                $amountGrams = -$newWeight;
            }
        }

        $updates[] = "amount_grams = ?";
        $params[] = $amountGrams;
        // Weight is computed dynamically: initial_weight_grams + SUM(consumption_log.amount_grams).
        // Updating amount_grams here is enough; no filaments UPDATE.
    }

    if (isset($data['consumption_date'])) {
        $updates[] = "consumption_date = ?";
        $params[] = $data['consumption_date'];
    }

    if (isset($data['note'])) {
        $updates[] = "description = ?";
        $params[] = $data['note'];
    }

    if (count($updates) > 0) {
        $params[] = $consumptionId;
        $sql = "UPDATE consumption_log SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    echo json_encode(['success' => true, 'message' => 'Záznam aktualizován']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
