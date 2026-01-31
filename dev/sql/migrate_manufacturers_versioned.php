<?php
declare(strict_types=1);

/**
 * Migrace výrobců na verzovanou tabulku s soft delete.
 * - manufacturers: id (řádek/verze), manufacturer_id (kořen), name, public, approved, created_at, created_by, invalidated_at, invalidated_by
 * - filaments: manufacturer VARCHAR -> manufacturer_id INT (logické id)
 * - spool_manufacturer: manufacturer_id zůstává jako logické id (odstraní se FK na manufacturers.id)
 *
 * Spusťte jednou na existující databázi. Před spuštěním záloha DB.
 */

require_once __DIR__ . '/../../config.php';

echo "Migrating manufacturers to versioned schema...\n";

try {
    // 1. Zjistit, zda už migrace proběhla (tabulka manufacturers má sloupec manufacturer_id)
    $stmt = $pdo->query("SHOW COLUMNS FROM manufacturers LIKE 'manufacturer_id'");
    if ($stmt && $stmt->rowCount() > 0) {
        echo "Migration already applied (manufacturer_id exists). Skipping.\n";
        exit(0);
    }

    // 2. Pokud existuje manufacturers_new, jde o pokračování po dřívějším selhání (DDL v MySQL implicitně commitne)
    $stmt = $pdo->query("SHOW TABLES LIKE 'manufacturers_new'");
    if ($stmt && $stmt->rowCount() > 0) {
        echo "Resuming after partial migration (manufacturers_new exists).\n";
        $pdo->beginTransaction();
        // Odstranit všechny FK odkazující na manufacturers
        $fkStmt = $pdo->query("
            SELECT DISTINCT TABLE_NAME, CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND REFERENCED_TABLE_NAME = 'manufacturers'
        ");
        $dropped = [];
        while ($fkStmt && ($fkRow = $fkStmt->fetch(PDO::FETCH_ASSOC))) {
            $table = $fkRow['TABLE_NAME'];
            $constraint = $fkRow['CONSTRAINT_NAME'];
            $key = $table . '.' . $constraint;
            if (isset($dropped[$key])) continue;
            $dropped[$key] = true;
            $pdo->exec("ALTER TABLE `" . str_replace('`', '``', $table) . "` DROP FOREIGN KEY `" . str_replace('`', '``', $constraint) . "`");
            echo "Dropped FK {$constraint} from {$table}.\n";
        }
        $pdo->exec("DROP TABLE manufacturers");
        $pdo->exec("RENAME TABLE manufacturers_new TO manufacturers");
        $pdo->commit();
        echo "Migration completed successfully (resume).\n";
        exit(0);
    }

    $pdo->beginTransaction();

    // 3. Vytvořit novou tabulku manufacturers_new
    $pdo->exec("
        CREATE TABLE manufacturers_new (
            id INT AUTO_INCREMENT PRIMARY KEY,
            manufacturer_id INT NOT NULL COMMENT 'kořen – společné pro všechny verze',
            name VARCHAR(255) NOT NULL,
            public TINYINT(1) NOT NULL DEFAULT 0,
            approved TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_by INT NOT NULL,
            invalidated_at DATETIME NULL DEFAULT NULL,
            invalidated_by INT NULL DEFAULT NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
            FOREIGN KEY (invalidated_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_manufacturer_id (manufacturer_id),
            INDEX idx_valid_approved (manufacturer_id, approved, invalidated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC
    ");
    echo "Created manufacturers_new.\n";

    // 4. Najít systémového uživatele pro created_by (první uživatel, nebo demo)
    $stmt = $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
    $systemUserId = $stmt->fetchColumn();
    if (!$systemUserId) {
        throw new RuntimeException('No users in database. Create at least one user before migration.');
    }

    // 4. Přesunout data: každý starý řádek manufacturers (id, name) -> jeden řádek v manufacturers_new
    //    manufacturer_id = staré id (aby filaments a spool_manufacturer mohly dál používat stejná čísla)
    $stmtOld = $pdo->query("SELECT id, name FROM manufacturers ORDER BY id");
    $stmtIns = $pdo->prepare("
        INSERT INTO manufacturers_new (manufacturer_id, name, public, approved, created_at, created_by, invalidated_at, invalidated_by)
        VALUES (?, ?, 1, 1, NOW(), ?, NULL, NULL)
    ");
    while ($row = $stmtOld->fetch(PDO::FETCH_ASSOC)) {
        $stmtIns->execute([(int)$row['id'], $row['name'], (int)$systemUserId]);
    }
    echo "Migrated " . $stmtOld->rowCount() . " manufacturers (as single approved versions).\n";

    // 6. filaments: přidat manufacturer_id, naplnit z manufacturer (VARCHAR), smazat manufacturer
    $pdo->exec("ALTER TABLE filaments ADD COLUMN manufacturer_id INT NULL DEFAULT NULL AFTER material");
    $stmtFil = $pdo->query("SELECT id, manufacturer FROM filaments WHERE manufacturer IS NOT NULL AND manufacturer != ''");
    $stmtMap = $pdo->prepare("SELECT manufacturer_id FROM manufacturers_new WHERE name = ? AND invalidated_at IS NULL LIMIT 1");
    $stmtUpdate = $pdo->prepare("UPDATE filaments SET manufacturer_id = ? WHERE id = ?");
    $updated = 0;
    while ($row = $stmtFil->fetch(PDO::FETCH_ASSOC)) {
        $stmtMap->execute([$row['manufacturer']]);
        $manId = $stmtMap->fetchColumn();
        if ($manId !== false) {
            $stmtUpdate->execute([(int)$manId, $row['id']]);
            $updated++;
        }
    }
    echo "Updated filaments.manufacturer_id for $updated rows.\n";
    $pdo->exec("ALTER TABLE filaments DROP COLUMN manufacturer");

    // 6. Odstranit VŠECHNY cizí klíče odkazující na manufacturers (více tabulek / více FK)
    $fkStmt = $pdo->query("
        SELECT DISTINCT TABLE_NAME, CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND REFERENCED_TABLE_NAME = 'manufacturers'
    ");
    $dropped = [];
    while ($fkStmt && ($fkRow = $fkStmt->fetch(PDO::FETCH_ASSOC))) {
        $table = $fkRow['TABLE_NAME'];
        $constraint = $fkRow['CONSTRAINT_NAME'];
        $key = $table . '.' . $constraint;
        if (isset($dropped[$key])) continue;
        $dropped[$key] = true;
        $pdo->exec("ALTER TABLE `" . str_replace('`', '``', $table) . "` DROP FOREIGN KEY `" . str_replace('`', '``', $constraint) . "`");
        echo "Dropped FK {$constraint} from {$table}.\n";
    }

    // 8. Smazat starou tabulku, přejmenovat novou
    $pdo->exec("DROP TABLE manufacturers");
    $pdo->exec("RENAME TABLE manufacturers_new TO manufacturers");
    echo "Replaced manufacturers table.\n";

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
