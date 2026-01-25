# 🚀 Průvodce nasazením na produkci

Tento dokument popisuje kompletní postup nasazení aplikace eFil na produkční server.

## 📋 Před nasazením

### Požadavky na server
- **PHP** 8.0 nebo vyšší
- **MySQL/MariaDB** 5.7 nebo vyšší
- **Webový server** (Apache/Nginx)
- **Rozšíření PHP**: PDO, PDO_MySQL, session
- **Oprávnění**: Možnost vytvářet databáze a tabulky

### Příprava lokálně
1. Zkontrolujte, že všechny testy projdou:
   ```bash
   php tests/run_all_tests.php
   ```
2. Otestujte aplikaci lokálně
3. Připravte produkční přihlašovací údaje k databázi

## 📦 Krok 1: Nahrání souborů na server

### Soubory k nahrání (přes FTP/SFTP)

**Nahrát:**
```
✓ api/                    (celý adresář)
✓ assets/                 (celý adresář)
✓ database/               (celý adresář - obsahuje schema.sql)
✓ config.php
✓ index.html
✓ init_db.php             (pouze pro první instalaci)
✓ update_schema.php       (pouze pokud aktualizujete existující DB)
✓ update_spool_schema.php (pouze pokud aktualizujete existující DB)
✓ README.md               (volitelné - dokumentace)
```

**NENAHRAVAT:**
```
✗ .env                    (vytvoříte na serveru)
✗ tests/                  (testovací soubory - nejsou potřeba na produkci)
✗ PROJECT_BLUEPRINT.md    (dokumentace - volitelné)
✗ .git/                   (git repozitář)
✗ .gitignore
```

### Struktura na serveru
```
/public_html/              (nebo /www/, /htdocs/ - podle hostingu)
├── api/
├── assets/
├── database/
├── config.php
├── index.html
├── init_db.php
└── .env                  (vytvoříte na serveru)
```

## 🔐 Krok 2: Vytvoření .env souboru

1. Připojte se k serveru přes FTP/SFTP nebo SSH
2. V kořenovém adresáři aplikace vytvořte soubor `.env`
3. Nastavte produkční přihlašovací údaje:

```env
DB_HOST=localhost
DB_NAME=vas_produkcni_db_nazev
DB_USER=vas_db_uzivatel
DB_PASS=vas_bezpecne_heslo
```

**⚠️ DŮLEŽITÉ:**
- Použijte silné heslo pro databázi
- `.env` soubor by měl mít oprávnění 600 (pouze vlastník může číst/zapisovat)
- Nikdy necommitujte `.env` do git repozitáře

### Nastavení oprávnění (přes SSH):
```bash
chmod 600 .env
```

## 🗄️ Krok 3: Vytvoření databáze

### Varianta A: Přes phpMyAdmin nebo hosting panel
1. Přihlaste se do phpMyAdmin nebo hosting panelu
2. Vytvořte novou databázi (např. `efil_production`)
3. Vytvořte uživatele databáze s oprávněními:
   - SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP
4. Zapište si přihlašovací údaje do `.env` souboru

### Varianta B: Přes SQL příkaz
```sql
CREATE DATABASE efil_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'efil_user'@'localhost' IDENTIFIED BY 'silne_heslo';
GRANT ALL PRIVILEGES ON efil_production.* TO 'efil_user'@'localhost';
FLUSH PRIVILEGES;
```

## 🔧 Krok 4: Inicializace databáze

### Pro novou instalaci:
1. Otevřete v prohlížeči:
   ```
   https://vas-domena.cz/init_db.php
   ```
2. Skript automaticky:
   - Vytvoří všechny tabulky
   - Vytvoří demo uživatele (`demo@efil.cz` / `demo1234`)
   - Naplní databázi demo daty

3. **PO INICIALIZACI SMAŽTE `init_db.php` ze serveru!**
   ```bash
   rm init_db.php
   ```
   Nebo přes FTP smažte soubor.

### Pro aktualizaci existující databáze:
Pokud již máte databázi s daty:
1. **ZÁLOHUJTE databázi před aktualizací!**
2. Spusťte migrační skripty v pořadí:
   ```
   https://vas-domena.cz/update_schema.php
   https://vas-domena.cz/update_spool_schema.php
   ```
3. Po dokončení smažte migrační skripty ze serveru

## ⚙️ Krok 5: Nastavení PHP na hostingu

### Doporučené změny v nastavení PHP:

**POVINNÉ změny:**
1. **`log_errors`**: Změňte z `Off` na **`On`**
   - Umožní logování chyb do souboru (důležité pro debugging v produkci)
   - Logy budou v adresáři `data/` (kontrolujte a promazávejte pravidelně)

2. **`session.cookie_httponly`**: Změňte z `Off` na **`On`**
   - Bezpečnostní opatření proti XSS útokům
   - Session cookies nebudou přístupné přes JavaScript

3. **`session.cookie_secure`**: Změňte z `Off` na **`On`** (pouze pokud máte HTTPS)
   - Zajistí, že session cookies se přenášejí pouze přes HTTPS
   - **POZOR:** Zapněte pouze pokud máte aktivní SSL certifikát!

**Volitelné změny:**
- Ostatní nastavení můžete ponechat na výchozích hodnotách
- `display_errors: Off` je správně (chyby se nebudou zobrazovat uživatelům)
- `post_max_size: 64M` a `upload_max_filesize: 60M` jsou dostatečné
- `memory_limit: 256M` je dostatečné
- `max_execution_time: 180` je dostatečné

**POZOR:** Pokud změníte `session.cookie_secure` na `On` bez HTTPS, sessions nebudou fungovat!

## 🔒 Krok 6: Bezpečnostní opatření

### 1. Oprávnění souborů
Nastavte správná oprávnění (přes SSH):
```bash
# Kořenový adresář
chmod 755 .

# PHP soubory
find . -type f -name "*.php" -exec chmod 644 {} \;

# Adresáře
find . -type d -exec chmod 755 {} \;

# .env soubor (pouze vlastník)
chmod 600 .env
```

### 2. Skrytí citlivých souborů
Vytvořte `.htaccess` v kořenovém adresáři (pro Apache):
```apache
# Zakázat přístup k .env
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

# Zakázat přístup k testům
<Directory "tests">
    Order allow,deny
    Deny from all
</Directory>

# Zakázat přístup k migračním skriptům po nasazení
<FilesMatch "(init_db|update_schema|update_spool_schema)\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 3. Error handling v produkci
Upravte `config.php` pro produkci (volitelné):
```php
// V produkci skrýt detaily chyb
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log('Database error: ' . $e->getMessage());
```

## ✅ Krok 7: Testování

### 1. Test přihlášení
- Otevřete: `https://vas-domena.cz/index.html`
- Přihlaste se demo účtem: `demo@efil.cz` / `demo1234`

### 2. Test API endpointů
Otevřete v prohlížeči (měli byste vidět JSON):
- `https://vas-domena.cz/api/auth/me.php` (po přihlášení)
- `https://vas-domena.cz/api/filaments/list.php` (po přihlášení)

### 3. Test funkcionalit
- ✅ Přidání nového filamentu
- ✅ Úprava filamentu
- ✅ Čerpání filamentu
- ✅ Přidání nového výrobce
- ✅ Správa cívek

## 🧹 Krok 8: Úklid po nasazení

### Smažte tyto soubory ze serveru:
```
✗ init_db.php              (po inicializaci)
✗ update_schema.php        (po aktualizaci, pokud použito)
✗ update_spool_schema.php  (po aktualizaci, pokud použito)
✗ tests/                   (celý adresář)
✗ PROJECT_BLUEPRINT.md     (volitelné)
```

### Nebo je zabezpečte přes .htaccess (viz výše)

## 📝 Krok 9: Vytvoření produkčního admin účtu

1. Přihlaste se demo účtem
2. Vytvořte nový účet přes registraci
3. Nebo přímo v databázi (pokud máte přístup):
   ```sql
   INSERT INTO users (email, password_hash, role)
   VALUES ('admin@vas-domena.cz', '$2y$10$...', 'admin_efil');
   ```
   (Heslo musí být zahashované pomocí `password_hash()`)

## 🔄 Aktualizace aplikace v budoucnu

1. Stáhněte nové soubory z git repozitáře
2. Nahrajte změněné soubory na server (přes FTP/SFTP)
3. Pokud se změnilo databázové schéma:
   - Zálohujte databázi
   - Nahrajte a spusťte migrační skripty
   - Smažte migrační skripty po dokončení
4. Otestujte aplikaci

## 🐛 Řešení problémů

### Chyba: "Database connection failed"
- Zkontrolujte `.env` soubor a přihlašovací údaje
- Ověřte, že databáze existuje
- Zkontrolujte oprávnění uživatele databáze

### Chyba: "Permission denied"
- Zkontrolujte oprávnění souborů (chmod)
- Ověřte, že webový server má přístup k souborům

### Data se nenačítají
- Otevřete konzoli prohlížeče (F12) a zkontrolujte chyby
- Zkontrolujte, že jste přihlášeni
- Ověřte, že API endpointy jsou přístupné

### Session nefunguje
- Zkontrolujte, že PHP má povolené sessions
- Ověřte oprávnění k adresáři pro session soubory

## 📞 Podpora

Pokud narazíte na problémy:
1. Zkontrolujte logy webového serveru
2. Zkontrolujte PHP error log
3. Ověřte, že všechny požadavky jsou splněny
4. Zkontrolujte dokumentaci v README.md

