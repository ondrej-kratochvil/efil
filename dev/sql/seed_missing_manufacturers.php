<?php
declare(strict_types=1);

/**
 * Doplní do tabulky manufacturers výrobce z init_db.php, kteří v DB chybí.
 * Spusťte z příkazové řádky: php dev/sql/seed_missing_manufacturers.php
 * Nebo v prohlížeči: .../dev/sql/seed_missing_manufacturers.php
 */

require_once __DIR__ . '/../../config.php';

// Stejný seznam jako v init_db.php
$manufacturerNames = [
    'Prusament (Prusa Research)', 'Fillamentum', 'Plasty Mladeč (PM)', 'Aurapol', 'Devil Design',
    'Sunlu', 'eSUN', 'Polymaker', 'ColorFabb', 'FormFutura', 'Extrudr', 'Fiberlogy',
    'Spectrum Filaments', 'Creality', 'Bambu Lab', 'Elegoo', 'Overture', 'Hatchbox',
    'Anycubic', '3DXTECH', 'BASF Forward AM', 'Kimya', 'Verbatim', 'Gembird',
    'C-TECH', 'AzureFilm', 'Eryone', 'Geeetech', 'Jayo', 'Nebula', '3DPower',
    'Kexcelled', 'Ziro', 'Prusa Polymers'
];

$isCli = (php_sapi_name() === 'cli');
function out(string $msg): void {
    global $isCli;
    echo $msg . ($isCli ? "\n" : "<br>");
}

out("Kontrola chybějících výrobců...");

try {
    // created_by musí být existující user – použijeme prvního uživatele
    $stmtUser = $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
    $firstUserId = $stmtUser->fetchColumn();
    if (!$firstUserId) {
        out("Chyba: V databázi neexistuje žádný uživatel. Nejprve vytvořte uživatele nebo spusťte init_db.php.");
        exit(1);
    }
    $createdBy = (int) $firstUserId;

    // Které názvy už v DB jsou (schválená, neinvalidovaná verze)
    $stmtExists = $pdo->prepare("
        SELECT 1 FROM manufacturers
        WHERE name = ? AND approved = 1 AND invalidated_at IS NULL
        LIMIT 1
    ");
    $missing = [];
    foreach ($manufacturerNames as $name) {
        $stmtExists->execute([$name]);
        if ($stmtExists->fetchColumn() === false) {
            $missing[] = $name;
        }
    }

    if (count($missing) === 0) {
        out("Všechny výrobce z init_db.php již v databázi máte. Nic se nedoplňuje.");
        exit(0);
    }

    out("Chybí " . count($missing) . " výrobců. Doplnění...");

    $stmtMax = $pdo->query("SELECT COALESCE(MAX(manufacturer_id), 0) + 1 AS next_id FROM manufacturers");
    $nextManId = (int) $stmtMax->fetchColumn();
    $stmtIns = $pdo->prepare("
        INSERT INTO manufacturers (manufacturer_id, name, public, approved, created_at, created_by)
        VALUES (?, ?, 1, 1, NOW(), ?)
    ");

    foreach ($missing as $name) {
        $stmtIns->execute([$nextManId, $name, $createdBy]);
        out("  + " . $name . " (manufacturer_id: $nextManId)");
        $nextManId++;
    }

    out("Hotovo. Doplněno " . count($missing) . " výrobců.");
} catch (PDOException $e) {
    out("Chyba: " . $e->getMessage());
    exit(1);
}
