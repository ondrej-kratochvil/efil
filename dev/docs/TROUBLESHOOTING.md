# 🐛 Řešení problémů při nasazení

## 500 Internal Server Error

### Možné příčiny a řešení:

#### 1. Problém s .htaccess
**Příznaky:** 500 error hned po nahrání souborů

**Řešení:**
- Dočasně přejmenujte `.htaccess` na `.htaccess.bak`
- Zkuste znovu načíst stránku
- Pokud to pomůže, `.htaccess` obsahuje nepodporované direktivy
- Postupně přidávejte zpět jednotlivé sekce a zjistěte, která způsobuje problém

**Minimální .htaccess (kompatibilní s většinou hostingů):**
```apache
# Zakázat přístup k .env
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

# Zakázat directory listing
Options -Indexes
```

#### 2. Chyba v PHP kódu
**Příznaky:** 500 error při spuštění konkrétního skriptu

**Řešení:**
1. Zkontrolujte PHP error log v adresáři `data/` na serveru
2. Dočasně přidejte na začátek `init_db.php`:
   ```php
   <?php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ini_set('log_errors', 1);
   ```
3. Zkuste znovu načíst stránku - uvidíte konkrétní chybu
4. **POZOR:** Po opravě odstraňte tyto řádky!

#### 3. Problém s databázovým připojením
**Příznaky:** 500 error při spuštění `init_db.php` nebo jiných API skriptů

**Řešení:**
1. Zkontrolujte `.env` soubor:
   - Existuje na serveru?
   - Má správné přihlašovací údaje?
   - Má správná oprávnění (chmod 600)?

2. Ověřte přihlašovací údaje:
   - Zkuste se připojit k databázi přes phpMyAdmin
   - Ověřte, že databáze existuje
   - Ověřte, že uživatel má potřebná oprávnění

3. Zkontrolujte `config.php`:
   - Otevřete `https://efil.sensio.cz/config.php` (dočasně)
   - Měli byste vidět prázdnou stránku nebo JSON (ne chybu)
   - Pokud vidíte chybu, problém je v připojení k DB

#### 4. Problém s oprávněními
**Příznaky:** 500 error, nelze zapisovat do databáze

**Řešení:**
1. Zkontrolujte oprávnění souborů:
   ```bash
   chmod 755 .          # Kořenový adresář
   chmod 644 *.php      # PHP soubory
   chmod 755 api/ assets/ database/  # Adresáře
   chmod 600 .env       # .env soubor
   ```

2. Zkontrolujte oprávnění databáze:
   - Uživatel musí mít: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP

#### 5. Problém s PHP verzí nebo rozšířeními
**Příznaky:** 500 error, chyby v logu o chybějících funkcích

**Řešení:**
1. Zkontrolujte PHP verzi (měla by být 8.0+)
2. Ověřte, že jsou nainstalována rozšíření:
   - PDO
   - PDO_MySQL
   - session

## Postup při řešení 500 error

### Krok 1: Zkontrolujte .htaccess
```bash
# Přes FTP přejmenujte
.htaccess → .htaccess.bak
```
Zkuste znovu načíst stránku. Pokud to pomůže, problém je v `.htaccess`.

### Krok 2: Zkontrolujte PHP error log
1. Přihlaste se přes FTP
2. Otevřete adresář `data/`
3. Najděte soubor s chybami (obvykle `error_log` nebo `php_errors.log`)
4. Přečtěte poslední chyby

### Krok 3: Povolte zobrazení chyb (dočasně)
Přidejte na začátek `init_db.php`:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
```

**POZOR:** Po opravě to odstraňte!

### Krok 4: Ověřte .env soubor
1. Zkontrolujte, že `.env` existuje na serveru
2. Ověřte formát (každý řádek: `KLÍČ=HODNOTA`)
3. Zkontrolujte, že nejsou mezery kolem `=`
4. Ověřte oprávnění: `chmod 600 .env`

### Krok 5: Test připojení k databázi
Vytvořte testovací soubor `test_db.php`:
```php
<?php
require_once 'config.php';
echo "Database connection: OK";
```
Otevřete v prohlížeči. Pokud vidíte chybu, problém je v připojení.

## Časté chyby

### "PDOException: SQLSTATE[HY000] [1049] Unknown database"
- Databáze neexistuje
- Vytvořte databázi přes phpMyAdmin nebo hosting panel

### "PDOException: SQLSTATE[HY000] [1045] Access denied"
- Špatné přihlašovací údaje v `.env`
- Uživatel nemá oprávnění k databázi

### "Call to undefined function password_hash()"
- PHP verze je nižší než 5.5
- Požádejte hostingu o upgrade PHP

### "Fatal error: Uncaught PDOException"
- Zkontrolujte PHP error log pro detaily
- Ověřte připojení k databázi

## Kontaktování podpory hostingu

Pokud nic nepomůže, kontaktujte podporu hostingu s těmito informacemi:
- Přesná chybová zpráva z PHP error log
- PHP verze
- Název domény
- Kdy problém nastal
- Co jste zkoušeli

