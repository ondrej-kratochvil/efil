<?php
// test_connection.php - Diagnostický skript pro zjištění problému
// POZOR: Po použití SMAŽTE tento soubor ze serveru!

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>eFil - Diagnostika připojení</h1>";
echo "<pre>";

// Test 1: PHP verze
echo "=== Test 1: PHP verze ===\n";
echo "PHP Version: " . phpversion() . "\n";
if (version_compare(phpversion(), '8.0.0', '>=')) {
    echo "✓ PHP verze je OK (8.0+)\n";
} else {
    echo "✗ PHP verze je příliš stará (potřebujete 8.0+)\n";
}
echo "\n";

// Test 2: PDO rozšíření
echo "=== Test 2: PDO rozšíření ===\n";
if (extension_loaded('pdo')) {
    echo "✓ PDO je nainstalováno\n";
} else {
    echo "✗ PDO není nainstalováno\n";
}
if (extension_loaded('pdo_mysql')) {
    echo "✓ PDO_MySQL je nainstalováno\n";
} else {
    echo "✗ PDO_MySQL není nainstalováno\n";
}
echo "\n";

// Test 3: .env soubor
echo "=== Test 3: .env soubor ===\n";
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "✓ .env soubor existuje\n";
    echo "Cesta: $envPath\n";
    if (is_readable($envPath)) {
        echo "✓ .env soubor je čitelný\n";
        $content = file_get_contents($envPath);
        if (strpos($content, 'DB_HOST') !== false) {
            echo "✓ .env obsahuje DB_HOST\n";
        } else {
            echo "✗ .env neobsahuje DB_HOST\n";
        }
        if (strpos($content, 'DB_NAME') !== false) {
            echo "✓ .env obsahuje DB_NAME\n";
        } else {
            echo "✗ .env neobsahuje DB_NAME\n";
        }
        if (strpos($content, 'DB_USER') !== false) {
            echo "✓ .env obsahuje DB_USER\n";
        } else {
            echo "✗ .env neobsahuje DB_USER\n";
        }
    } else {
        echo "✗ .env soubor není čitelný (zkontrolujte oprávnění)\n";
    }
} else {
    echo "✗ .env soubor neexistuje!\n";
    echo "Cesta: $envPath\n";
}
echo "\n";

// Test 4: Načtení .env
echo "=== Test 4: Načtení .env ===\n";
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim($value);
    }
    
    $host = $env['DB_HOST'] ?? 'localhost';
    $db   = $env['DB_NAME'] ?? 'efil_db';
    $user = $env['DB_USER'] ?? 'root';
    $pass = $env['DB_PASS'] ?? '';
    
    echo "DB_HOST: " . ($host ?: '(prázdné)') . "\n";
    echo "DB_NAME: " . ($db ?: '(prázdné)') . "\n";
    echo "DB_USER: " . ($user ?: '(prázdné)') . "\n";
    echo "DB_PASS: " . ($pass ? '***' : '(prázdné)') . "\n";
} else {
    echo "✗ Nelze načíst .env (soubor neexistuje)\n";
}
echo "\n";

// Test 5: Připojení k databázi
echo "=== Test 5: Připojení k databázi ===\n";
if (file_exists($envPath)) {
    try {
        $host = $env['DB_HOST'] ?? 'localhost';
        $user = $env['DB_USER'] ?? 'root';
        $pass = $env['DB_PASS'] ?? '';
        
        // Zkusit připojení bez databáze
        $dsn = "mysql:host=$host;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        echo "✓ Připojení k MySQL serveru úspěšné\n";
        
        // Zkontrolovat, zda databáze existuje
        $db = $env['DB_NAME'] ?? 'efil_db';
        $stmt = $pdo->query("SHOW DATABASES LIKE '$db'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Databáze '$db' existuje\n";
        } else {
            echo "⚠ Databáze '$db' neexistuje (bude vytvořena při inicializaci)\n";
        }
    } catch (PDOException $e) {
        echo "✗ Chyba připojení k databázi: " . $e->getMessage() . "\n";
        echo "Kód chyby: " . $e->getCode() . "\n";
    }
} else {
    echo "✗ Nelze testovat připojení (.env neexistuje)\n";
}
echo "\n";

// Test 6: Oprávnění souborů
echo "=== Test 6: Oprávnění souborů ===\n";
$files = ['.env', 'config.php', 'init_db.php'];
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        echo "$file: $perms ";
        if (is_readable($path)) {
            echo "✓ čitelný ";
        } else {
            echo "✗ není čitelný ";
        }
        if (is_writable($path)) {
            echo "✓ zapisovatelný";
        } else {
            echo "✗ není zapisovatelný";
        }
        echo "\n";
    }
}
echo "\n";

// Test 7: .htaccess
echo "=== Test 7: .htaccess ===\n";
$htaccessPath = __DIR__ . '/.htaccess';
if (file_exists($htaccessPath)) {
    echo "✓ .htaccess existuje\n";
    $content = file_get_contents($htaccessPath);
    // Zkontrolovat problematické direktivy
    $problematic = ['php_value', 'php_flag', 'ServerSignature'];
    foreach ($problematic as $directive) {
        if (stripos($content, $directive) !== false) {
            echo "⚠ .htaccess obsahuje '$directive' (může způsobovat problémy na některých hostinzích)\n";
        }
    }
} else {
    echo "ℹ .htaccess neexistuje (není problém)\n";
}
echo "\n";

echo "</pre>";
echo "<p><strong>POZOR:</strong> Po dokončení diagnostiky SMAŽTE tento soubor ze serveru!</p>";
?>

