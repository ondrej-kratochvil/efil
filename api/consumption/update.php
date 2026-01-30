<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/inventory.php';
require_once __DIR__ . '/../helpers/demo.php';

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
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatný JSON']);
    exit;
}

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing consumption ID']);
    exit;
}

try {
    $consumptionId = $data['id'];
    $userId = (int) $_SESSION['user_id'];
    $inventoryId = getInventoryIdForUser($pdo, $userId);
    if ($inventoryId === null) {
        http_response_code(404);
        echo json_encode(['error' => 'No inventory found']);
        exit;
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

    checkDemoModeAccess($pdo, $userId, $consumption['is_demo'] ?? null, 'V demo režimu nelze upravovat data');

    // Update consumption record
    $updates = [];
    $params = [];
    $originalAmount = (int) ($consumption['old_weight'] ?? 0);

    if (isset($data['amount_grams']) || isset($data['consumed_weight'])) {
        // Prefer amount_grams (with sign) when provided; otherwise derive from consumed_weight and original sign
        if (isset($data['amount_grams'])) {
            $amountGrams = (int) $data['amount_grams'];
            if ($amountGrams === 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Hmotnost musí být větší než nula. Pro zrušení záznamu použijte smazání.']);
                exit;
            }
            if (($originalAmount >= 0 && $amountGrams < 0) || ($originalAmount < 0 && $amountGrams > 0)) {
                http_response_code(400);
                echo json_encode(['error' => 'Nelze změnit typ záznamu (čerpání / korekce). Upravte pouze hodnotu.']);
                exit;
            }
        } else {
            $newWeight = (int) $data['consumed_weight'];
            if ($newWeight <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Hmotnost musí být větší než nula. Pro zrušení záznamu použijte smazání.']);
                exit;
            }
            $amountGrams = $originalAmount > 0 ? $newWeight : -$newWeight;
        }

        $updates[] = "amount_grams = ?";
        $params[] = $amountGrams;
    }

    if (isset($data['consumption_date'])) {
        $updates[] = "consumption_date = ?";
        $params[] = $data['consumption_date'];
    }

    if (isset($data['note'])) {
        $updates[] = "description = ?";
        $params[] = $data['note'];
    }

    if (count($updates) === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Žádná pole k aktualizaci. Uveďte consumed_weight, consumption_date nebo note.']);
        exit;
    }

    $params[] = $consumptionId;
    $sql = "UPDATE consumption_log SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'Záznam aktualizován']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
