<?php
// init_db.php - Sets up the database and seeds it with demo data

// Load environment variables if .env exists
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'efil_db';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = 'utf8mb4';

echo "Initializing database...\n";

try {
    // 1. Connect to MySQL without database to create it if needed
    $dsnNoDb = "mysql:host=$host;charset=$charset";
    $pdoTemp = new PDO($dsnNoDb, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Check if database exists and create it if not
    $stmt = $pdoTemp->query("SHOW DATABASES LIKE '$db'");
    if ($stmt->rowCount() == 0) {
        echo "Creating database '$db'...\n";
        $pdoTemp->exec("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Database created successfully.\n";
    } else {
        echo "Database '$db' already exists.\n";
    }
    
    // 2. Now connect to the database
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // 3. Drop existing tables if they exist (in correct order due to foreign keys)
    echo "Dropping existing tables if any...\n";
    $tables = ['consumption_log', 'filaments', 'spool_library', 'inventory_members', 'inventory_access', 'inventories', 'manufacturers', 'users'];
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // 4. Read schema and create tables
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    $queries = explode(';', $schema);
    
    echo "Creating tables...\n";
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    echo "Tables created successfully.\n";

    // 5. Check if we need to seed
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() > 0) {
        echo "Database already contains users. Skipping seed.\n";
        exit;
    }

    echo "Seeding data...\n";

    // 6. Seed data - Create Demo User
    $email = 'demo@efil.cz';
    $pass = password_hash('demo1234', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'user')");
    $stmt->execute([$email, $pass]);
    $userId = $pdo->lastInsertId();

    // 7. Seed data - Create Inventory
    $stmt = $pdo->prepare("INSERT INTO inventories (owner_id, name) VALUES (?, 'Demo Dílna')");
    $stmt->execute([$userId]);
    $invId = $pdo->lastInsertId();

    // 8. Seed data - Create Manufacturers
    $manufacturers = [
        'Prusament (Prusa Research)', 'Fillamentum', 'Plasty Mladeč (PM)', 'Aurapol', 'Devil Design',
        'Sunlu', 'eSUN', 'Polymaker', 'ColorFabb', 'FormFutura', 'Extrudr', 'Fiberlogy',
        'Spectrum Filaments', 'Creality', 'Bambu Lab', 'Elegoo', 'Overture', 'Hatchbox',
        'Anycubic', '3DXTECH', 'BASF Forward AM', 'Kimya', 'Verbatim', 'Gembird',
        'C-TECH', 'AzureFilm', 'Eryone', 'Geeetech', 'Jayo', 'Nebula', '3DPower',
        'Kexcelled', 'Ziro'
    ];
    
    $stmtMan = $pdo->prepare("INSERT IGNORE INTO manufacturers (name) VALUES (?)");
    foreach ($manufacturers as $man) {
        $stmtMan->execute([$man]);
    }
    echo "Manufacturers seeded.\n";

    // 9. Seed data - Create Filaments
    $filaments = [
        ['PLA (Standard)', 'Prusa Polymers', 'Galaxy černá', '#333333', 850, 'Hlavní regál'],
        ['PLA (Standard)', 'Prusa Polymers', 'Prusa Oranžová', '#FF6A13', 400, 'Hlavní regál'],
        ['PETG', 'Prusa Polymers', 'Šedá', '#8E9089', 950, 'Box 1'],
        ['PETG', 'Devil Design', 'Průhledná / Čirá', '#E8E8E8', 200, 'Box 1'],
        ['ASA', 'Prusa Polymers', 'Černá', '#000000', 1000, 'Garáž'],
        ['PLA (Standard)', 'Gembird', 'Červená', '#C12E1F', 700, 'Šuplík'],
        ['TPU 95A (Flexibilní)', 'Fiberlogy', 'Černá', '#000000', 450, 'Box 2'],
    ];

    $stmt = $pdo->prepare("INSERT INTO filaments (inventory_id, user_display_id, material, manufacturer, color_name, color_hex, initial_weight_grams, location, purchase_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    foreach ($filaments as $idx => $f) {
        $stmt->execute([$invId, $idx + 1, $f[0], $f[1], $f[2], $f[3], $f[4], $f[5]]);
    }

    echo "Database initialized with demo data.\n";
    echo "User: $email\nPass: demo1234\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
