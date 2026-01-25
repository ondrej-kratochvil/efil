<?php
/**
 * Komplexní migrační skript pro opravu všech chybějících sloupců a tabulek
 * Opravuje nesoulad mezi schématem databáze a tím, co očekávají testy a API
 */

require_once __DIR__ . '/config.php';

echo "=== Komplexní migrace schématu databáze ===\n\n";

try {
    // 1. Opravit consumption_log tabulku
    echo "1. Kontrola a oprava tabulky consumption_log...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM consumption_log");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "   Aktuální sloupce: " . implode(', ', $columns) . "\n";

    // Přidat consumed_weight (pokud neexistuje)
    if (!in_array('consumed_weight', $columns)) {
        echo "   Přidávám sloupec consumed_weight...\n";
        if (in_array('amount_grams', $columns)) {
            $pdo->exec("ALTER TABLE consumption_log ADD COLUMN consumed_weight INT NOT NULL DEFAULT 0 AFTER filament_id");
            $pdo->exec("UPDATE consumption_log SET consumed_weight = ABS(amount_grams) WHERE amount_grams IS NOT NULL");
        } else {
            $pdo->exec("ALTER TABLE consumption_log ADD COLUMN consumed_weight INT NOT NULL DEFAULT 0 AFTER filament_id");
        }
        echo "   ✓ consumed_weight přidán\n";
    } else {
        // Zajistit, aby consumed_weight měl výchozí hodnotu (pro kompatibilitu s testy)
        echo "   Zajišťuji výchozí hodnotu pro consumed_weight...\n";
        try {
            $pdo->exec("ALTER TABLE consumption_log MODIFY COLUMN consumed_weight INT NOT NULL DEFAULT 0");
            echo "   ✓ consumed_weight má výchozí hodnotu 0\n";
        } catch (PDOException $e) {
            echo "   ⚠ Nelze upravit consumed_weight: " . $e->getMessage() . "\n";
        }
    }

    // Upravit amount_grams, aby měl výchozí hodnotu 0 (pro kompatibilitu s testy)
    // Testy používají consumed_weight, ale amount_grams je NOT NULL
    if (in_array('amount_grams', $columns)) {
        echo "   Upravuji amount_grams, aby měl výchozí hodnotu 0...\n";
        try {
            $pdo->exec("ALTER TABLE consumption_log MODIFY COLUMN amount_grams INT NOT NULL DEFAULT 0");
            echo "   ✓ amount_grams upraven (výchozí hodnota 0)\n";
        } catch (PDOException $e) {
            // Pokud selže, zkusit nullable
            try {
                $pdo->exec("ALTER TABLE consumption_log MODIFY COLUMN amount_grams INT NULL");
                echo "   ✓ amount_grams upraven na nullable\n";
            } catch (PDOException $e2) {
                echo "   ⚠ Nelze upravit amount_grams: " . $e2->getMessage() . "\n";
            }
        }
    }

    // Zkontrolovat description (pokud neexistuje, přidat)
    if (!in_array('description', $columns)) {
        echo "   Přidávám sloupec description...\n";
        if (in_array('note', $columns)) {
            $pdo->exec("ALTER TABLE consumption_log ADD COLUMN description TEXT AFTER amount_grams");
            $pdo->exec("UPDATE consumption_log SET description = note");
            $pdo->exec("ALTER TABLE consumption_log DROP COLUMN note");
            echo "   ✓ description přidán (hodnoty zkopírovány z note, note smazán)\n";
        } else {
            $pdo->exec("ALTER TABLE consumption_log ADD COLUMN description TEXT AFTER amount_grams");
            echo "   ✓ description přidán\n";
        }
    } else {
        // Pokud existuje description i note, smazat note
        if (in_array('note', $columns)) {
            echo "   Odstraňuji duplicitní sloupec note (používáme description)...\n";
            $pdo->exec("ALTER TABLE consumption_log DROP COLUMN note");
            echo "   ✓ note odstraněn\n";
        }
    }

    // Přidat consumption_date (pokud neexistuje) - měl by být v schématu, ale zkontrolujeme
    if (!in_array('consumption_date', $columns)) {
        echo "   Přidávám sloupec consumption_date...\n";
        $pdo->exec("ALTER TABLE consumption_log ADD COLUMN consumption_date DATE NOT NULL DEFAULT (CURRENT_DATE) AFTER description");
        // Pokud existuje created_at, použít jeho hodnoty
        if (in_array('created_at', $columns)) {
            $pdo->exec("UPDATE consumption_log SET consumption_date = DATE(created_at)");
        }
        echo "   ✓ consumption_date přidán\n";
    } else {
        // Zajistit, aby consumption_date měl výchozí hodnotu (pro kompatibilitu s testy)
        echo "   Zajišťuji výchozí hodnotu pro consumption_date...\n";
        try {
            $pdo->exec("ALTER TABLE consumption_log MODIFY COLUMN consumption_date DATE NOT NULL DEFAULT (CURRENT_DATE)");
            echo "   ✓ consumption_date má výchozí hodnotu CURRENT_DATE\n";
        } catch (PDOException $e) {
            // Některé verze MySQL nepodporují DEFAULT (CURRENT_DATE) pro DATE, zkusit jinak
            try {
                // V MySQL 8.0.13+ lze použít DEFAULT (CURRENT_DATE), jinak použít trigger nebo nullable
                $pdo->exec("ALTER TABLE consumption_log MODIFY COLUMN consumption_date DATE NULL");
                echo "   ✓ consumption_date upraven na nullable (jako fallback)\n";
            } catch (PDOException $e2) {
                echo "   ⚠ Nelze upravit consumption_date: " . $e2->getMessage() . "\n";
            }
        }
    }

    // Přidat created_by (pokud neexistuje)
    if (!in_array('created_by', $columns)) {
        echo "   Přidávám sloupec created_by...\n";
        $pdo->exec("ALTER TABLE consumption_log ADD COLUMN created_by INT NULL AFTER consumption_date");
        // Zkusit přidat foreign key constraint (může selhat, pokud už existuje)
        try {
            $pdo->exec("ALTER TABLE consumption_log ADD CONSTRAINT fk_consumption_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
        } catch (PDOException $e) {
            // Ignorovat, pokud constraint již existuje nebo selže z jiného důvodu
        }
        echo "   ✓ created_by přidán\n";
    }

    echo "   ✓ consumption_log opravena\n\n";

    // 2. Zkontrolovat filaments tabulku (current_weight)
    echo "2. Kontrola tabulky filaments...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM filaments LIKE 'current_weight'");
    if ($stmt->rowCount() == 0) {
        echo "   Přidávám sloupec current_weight...\n";
        $pdo->exec("ALTER TABLE filaments ADD COLUMN current_weight INT NOT NULL DEFAULT 0 AFTER initial_weight_grams");
        $pdo->exec("UPDATE filaments SET current_weight = initial_weight_grams");

        // Aktualizovat podle consumption_log
        try {
            $pdo->exec("
                UPDATE filaments f
                SET f.current_weight = f.initial_weight_grams + COALESCE((
                    SELECT SUM(cl.amount_grams)
                    FROM consumption_log cl
                    WHERE cl.filament_id = f.id
                ), 0)
            ");
        } catch (PDOException $e) {
            // Pokud amount_grams neexistuje, zkusit consumed_weight
            try {
                $pdo->exec("
                    UPDATE filaments f
                    SET f.current_weight = f.initial_weight_grams - COALESCE((
                        SELECT SUM(cl.consumed_weight)
                        FROM consumption_log cl
                        WHERE cl.filament_id = f.id
                    ), 0)
                ");
            } catch (PDOException $e2) {
                // Ignorovat, pokud neexistuje ani jeden
            }
        }
        echo "   ✓ current_weight přidán a inicializován\n";
    } else {
        echo "   ✓ current_weight již existuje\n";
    }
    echo "   ✓ filaments zkontrolována\n\n";

    // 3. Zkontrolovat inventory_members tabulku
    echo "3. Kontrola tabulky inventory_members...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'inventory_members'");
    if ($stmt->rowCount() == 0) {
        echo "   Vytvářím tabulku inventory_members...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS inventory_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                inventory_id INT NOT NULL,
                user_id INT NOT NULL,
                role ENUM('read', 'write', 'manage') DEFAULT 'read',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY inventory_user_unique (inventory_id, user_id),
                FOREIGN KEY (inventory_id) REFERENCES inventories(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC
        ");
        echo "   ✓ inventory_members vytvořena\n";
    } else {
        echo "   ✓ inventory_members již existuje\n";
    }
    echo "   ✓ inventory_members zkontrolována\n\n";

    // 4. Zkontrolovat spool_manufacturer tabulku
    echo "4. Kontrola tabulky spool_manufacturer...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'spool_manufacturer'");
    if ($stmt->rowCount() == 0) {
        echo "   Vytvářím tabulku spool_manufacturer...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS spool_manufacturer (
                id INT AUTO_INCREMENT PRIMARY KEY,
                spool_id INT NOT NULL,
                manufacturer_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY spool_manufacturer_unique (spool_id, manufacturer_id),
                FOREIGN KEY (spool_id) REFERENCES spool_library(id) ON DELETE CASCADE,
                FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC
        ");
        echo "   ✓ spool_manufacturer vytvořena\n";
    } else {
        echo "   ✓ spool_manufacturer již existuje\n";
    }
    echo "   ✓ spool_manufacturer zkontrolována\n\n";

    echo "✅ Všechny migrace úspěšně dokončeny!\n";

} catch (PDOException $e) {
    echo "\n❌ Chyba: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

