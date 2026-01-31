CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NULL COMMENT 'NULL = účet čeká na nastavení hesla',
    role ENUM('user', 'admin_efil') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY email_unique (email(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS inventories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_demo BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS inventory_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    access_code VARCHAR(255) NOT NULL,
    permission ENUM('read', 'write', 'manage') DEFAULT 'read',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY access_code_unique (access_code(191)),
    FOREIGN KEY (inventory_id) REFERENCES inventories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS inventory_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('read', 'write', 'manage') DEFAULT 'read',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY inventory_user_unique (inventory_id, user_id),
    FOREIGN KEY (inventory_id) REFERENCES inventories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS manufacturers (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS spool_library (
    id INT AUTO_INCREMENT PRIMARY KEY,
    weight_grams INT,
    color VARCHAR(50),
    material VARCHAR(50),
    outer_diameter_mm INT,
    width_mm INT,
    visual_description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- M:N relationship between spool types and manufacturers (manufacturer_id = logické id z manufacturers.manufacturer_id)
CREATE TABLE IF NOT EXISTS spool_manufacturer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spool_id INT NOT NULL,
    manufacturer_id INT NOT NULL COMMENT 'logické id výrobce (manufacturers.manufacturer_id)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY spool_manufacturer_unique (spool_id, manufacturer_id),
    FOREIGN KEY (spool_id) REFERENCES spool_library(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS filaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    user_display_id INT NOT NULL,
    material VARCHAR(50) NOT NULL,
    manufacturer_id INT NULL COMMENT 'logické id výrobce (manufacturers.manufacturer_id)',
    color_name VARCHAR(255) NOT NULL,
    color_hex VARCHAR(7) NOT NULL,
    spool_type_id INT,
    initial_weight_grams INT NOT NULL,
    price INT,
    purchase_date DATE,
    seller VARCHAR(255),
    location TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventories(id) ON DELETE CASCADE,
    FOREIGN KEY (spool_type_id) REFERENCES spool_library(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS consumption_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filament_id INT NOT NULL,
    amount_grams INT NOT NULL,
    description TEXT,
    consumption_date DATE NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (filament_id) REFERENCES filaments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
