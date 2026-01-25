<?php
/**
 * Migrační skript pro přidání sloupců consumed_weight a note do tabulky consumption_log
 * Tyto sloupce jsou vyžadovány některými API soubory a testy
 */

require_once __DIR__ . '/config.php';

echo "=== Migrace schématu consumption_log ===\n\n";

try {
    // Zkontrolovat aktuální sloupce
    $stmt = $pdo->query("SHOW COLUMNS FROM consumption_log");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Aktuální sloupce: " . implode(', ', $columns) . "\n\n";
    
    // 1. Přidat consumed_weight (pokud neexistuje)
    if (!in_array('consumed_weight', $columns)) {
        echo "1. Přidávám sloupec consumed_weight...\n";
        
        // Pokud existuje amount_grams, použít jeho absolutní hodnoty
        if (in_array('amount_grams', $columns)) {
            $pdo->exec("ALTER TABLE consumption_log ADD COLUMN consumed_weight INT NOT NULL AFTER filament_id");
            $pdo->exec("UPDATE consumption_log SET consumed_weight = ABS(amount_grams)");
            echo "   ✓ Sloupec consumed_weight přidán (hodnoty zkopírovány z amount_grams jako absolutní hodnoty)\n\n";
        } else {
            $pdo->exec("ALTER TABLE consumption_log ADD COLUMN consumed_weight INT NOT NULL AFTER filament_id");
            echo "   ✓ Sloupec consumed_weight přidán\n\n";
        }
    } else {
        echo "1. ✓ Sloupec consumed_weight již existuje\n\n";
    }
    
    // 2. Zkontrolovat description (pokud neexistuje, přidat)
    if (!in_array('description', $columns)) {
        echo "2. Přidávám sloupec description...\n";
        
        // Pokud existuje note, použít jeho hodnoty a pak ho smazat
        if (in_array('note', $columns)) {
            $pdo->exec("ALTER TABLE consumption_log ADD COLUMN description TEXT AFTER amount_grams");
            $pdo->exec("UPDATE consumption_log SET description = note");
            $pdo->exec("ALTER TABLE consumption_log DROP COLUMN note");
            echo "   ✓ Sloupec description přidán (hodnoty zkopírovány z note, note smazán)\n\n";
        } else {
            $pdo->exec("ALTER TABLE consumption_log ADD COLUMN description TEXT AFTER amount_grams");
            echo "   ✓ Sloupec description přidán\n\n";
        }
    } else {
        // Pokud existuje description i note, smazat note
        if (in_array('note', $columns)) {
            echo "2. Odstraňuji duplicitní sloupec note (používáme description)...\n";
            $pdo->exec("ALTER TABLE consumption_log DROP COLUMN note");
            echo "   ✓ Sloupec note odstraněn\n\n";
        } else {
            echo "2. ✓ Sloupec description již existuje\n\n";
        }
    }
    
    echo "✅ Migrace úspěšně dokončena!\n";
    
} catch (PDOException $e) {
    echo "\n❌ Chyba: " . $e->getMessage() . "\n";
    exit(1);
}
