<?php
require_once __DIR__ . '/../../config.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Helper for JSON response
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Handle GET for single item details if needed (for edit)
// But for now, let's focus on POST for Create/Update
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];

// Get user's inventory (owned or shared with write/manage permission)
$sql = "
    SELECT i.id, i.is_demo, 'owner' as role
    FROM inventories i
    WHERE i.owner_id = ?
    UNION
    SELECT i.id, i.is_demo, im.role
    FROM inventories i
    JOIN inventory_members im ON i.id = im.inventory_id
    WHERE im.user_id = ? AND im.role IN ('write', 'manage')
    LIMIT 1
";
$stmtInv = $pdo->prepare($sql);
$stmtInv->execute([$userId, $userId]);
$inv = $stmtInv->fetch();

if (!$inv) {
    jsonResponse(['error' => 'No inventory found or insufficient permissions'], 404);
}

$inventoryId = $inv['id'];

// Check if user is admin_efil
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$isAdmin = ($user && $user['role'] === 'admin_efil');

// Check if demo mode (and user is not admin)
if ($inv['is_demo'] && !$isAdmin) {
    jsonResponse(['error' => 'V demo režimu nelze upravovat data. Vytvořte si vlastní účet pro plný přístup.'], 403);
}

// Determine if Update or Create
$id = $input['id'] ?? null;
$userDisplayId = !empty($input['user_display_id']) ? (int)$input['user_display_id'] : null;
$mat = $input['mat'] ?? '';
$man = $input['man'] ?? '';
$color = $input['color'] ?? '';
$hex = $input['hex'] ?? '#000000';
$weight = (int)($input['g'] ?? 1000);
$loc = $input['loc'] ?? '';
$price = !empty($input['price']) ? (int)$input['price'] : null;
$seller = $input['seller'] ?? '';
$date = !empty($input['date']) ? $input['date'] : null;

$spoolId = !empty($input['spool_id']) ? (int)$input['spool_id'] : null;

// Basic validation
if (!$mat || !$color) {
    jsonResponse(['error' => 'Missing required fields'], 400);
}

try {
    // Ensure manufacturer exists in manufacturers table
    if (!empty($man)) {
        $stmtMan = $pdo->prepare("SELECT id FROM manufacturers WHERE name = ?");
        $stmtMan->execute([$man]);
        $existingMan = $stmtMan->fetch();

        if (!$existingMan) {
            // Create new manufacturer
            $stmtInsert = $pdo->prepare("INSERT INTO manufacturers (name) VALUES (?)");
            $stmtInsert->execute([$man]);
        }
    }

    if ($id) {
        // UPDATE
        // Verify ownership
        $check = $pdo->prepare("SELECT id FROM filaments WHERE id = ? AND inventory_id = ?");
        $check->execute([$id, $inventoryId]);
        if (!$check->fetch()) {
            jsonResponse(['error' => 'Filament not found or access denied'], 403);
        }

        // Logic for weight update is tricky because of consumption logs.
        // For simple edit, we update 'initial_weight_grams' but we must account for existing logs?
        // Actually, 'initial_weight_grams' usually means weight when spool was added.
        // If user edits "Current Weight" in form, we might need to adjust initial or add a "correction" log.
        // For simplicity in Phase 2, let's assume the form edits static properties.
        // Changing weight directly is usually an "inventory correction" -> consumption_log.
        // Let's assume input['g'] is the NEW Initial Weight if we are editing properties,
        // OR we just update the static fields.
        // BUT Blueprint says: "Aktuální zůstatek (Netto) = initial + SUM(log)".
        // If user edits the "Weight" field in editor, they probably mean "This was the initial weight".

        // Check for duplicate user_display_id if provided
        if ($userDisplayId !== null) {
            $checkDup = $pdo->prepare("SELECT id FROM filaments WHERE inventory_id = ? AND user_display_id = ? AND id != ?");
            $checkDup->execute([$inventoryId, $userDisplayId, $id]);
            if ($checkDup->fetch()) {
                jsonResponse(['error' => 'Číslo filamentu již existuje v této evidenci. Zvolte jiné číslo.'], 400);
            }
        }

        $sql = "UPDATE filaments SET
                user_display_id = COALESCE(?, user_display_id),
                material = ?, manufacturer = ?, color_name = ?, color_hex = ?,
                location = ?, price = ?, seller = ?, purchase_date = ?, initial_weight_grams = ?, spool_type_id = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userDisplayId, $mat, $man, $color, $hex, $loc, $price, $seller, $date, $weight, $spoolId, $id]);

        jsonResponse(['message' => 'Updated', 'id' => $id]);

    } else {
        // CREATE
        // Determine user_display_id: use provided or generate next available
        if ($userDisplayId !== null) {
            // Check for duplicate
            $checkDup = $pdo->prepare("SELECT id FROM filaments WHERE inventory_id = ? AND user_display_id = ?");
            $checkDup->execute([$inventoryId, $userDisplayId]);
            if ($checkDup->fetch()) {
                jsonResponse(['error' => 'Číslo filamentu již existuje v této evidenci. Zvolte jiné číslo.'], 400);
            }
            $finalDisplayId = $userDisplayId;
        } else {
            // Generate user_display_id automatically
            $stmtMax = $pdo->prepare("SELECT MAX(user_display_id) as maxid FROM filaments WHERE inventory_id = ?");
            $stmtMax->execute([$inventoryId]);
            $finalDisplayId = ($stmtMax->fetch()['maxid'] ?? 0) + 1;
        }

        $sql = "INSERT INTO filaments (
                    inventory_id, user_display_id, material, manufacturer,
                    color_name, color_hex, initial_weight_grams, location,
                    price, seller, purchase_date, spool_type_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$inventoryId, $finalDisplayId, $mat, $man, $color, $hex, $weight, $loc, $price, $seller, $date, $spoolId]);

        jsonResponse(['message' => 'Created', 'id' => $pdo->lastInsertId()], 201);
    }

} catch (Exception $e) {
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
