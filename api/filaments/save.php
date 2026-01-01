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

// Get user's inventory
$stmtInv = $pdo->prepare("SELECT id FROM inventories WHERE owner_id = ? LIMIT 1");
$stmtInv->execute([$userId]);
$inv = $stmtInv->fetch();

if (!$inv) {
    jsonResponse(['error' => 'No inventory found'], 404);
}

$inventoryId = $inv['id'];

// Determine if Update or Create
$id = $input['id'] ?? null;
$mat = $input['mat'] ?? '';
$man = $input['man'] ?? '';
$color = $input['color'] ?? '';
$hex = $input['hex'] ?? '#000000';
$weight = (int)($input['g'] ?? 1000);
$loc = $input['loc'] ?? '';
$price = !empty($input['price']) ? (int)$input['price'] : null;
$seller = $input['seller'] ?? '';
$date = !empty($input['date']) ? $input['date'] : null;

// Basic validation
if (!$mat || !$color) {
    jsonResponse(['error' => 'Missing required fields'], 400);
}

try {
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
        
        $sql = "UPDATE filaments SET 
                material = ?, manufacturer = ?, color_name = ?, color_hex = ?, 
                location = ?, price = ?, seller = ?, purchase_date = ?, initial_weight_grams = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mat, $man, $color, $hex, $loc, $price, $seller, $date, $weight, $id]);
        
        jsonResponse(['message' => 'Updated', 'id' => $id]);

    } else {
        // CREATE
        // Generate user_display_id
        $stmtMax = $pdo->prepare("SELECT MAX(user_display_id) as maxid FROM filaments WHERE inventory_id = ?");
        $stmtMax->execute([$inventoryId]);
        $nextId = ($stmtMax->fetch()['maxid'] ?? 0) + 1;

        $sql = "INSERT INTO filaments (
                    inventory_id, user_display_id, material, manufacturer, 
                    color_name, color_hex, initial_weight_grams, location, 
                    price, seller, purchase_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$inventoryId, $nextId, $mat, $man, $color, $hex, $weight, $loc, $price, $seller, $date]);
        
        jsonResponse(['message' => 'Created', 'id' => $pdo->lastInsertId()], 201);
    }

} catch (Exception $e) {
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
