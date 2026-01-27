# ✅ Post-Deployment Checklist

Kontrolní seznam úkolů po úspěšném nasazení aplikace na produkci.

## 🧹 Úklid po nasazení

### 1. Smazat diagnostické a migrační skripty

**POVINNÉ - Bezpečnostní riziko!**

Smažte tyto soubory ze serveru (přes FTP/SFTP):
```
✗ test_connection.php      (diagnostický skript)
✗ init_db.php              (inicializační skript - již není potřeba)
✗ update_schema.php        (migrační skript - pokud byl použit)
✗ update_spool_schema.php  (migrační skript - pokud byl použit)
```

**Alternativa:** Pokud je chcete ponechat pro případné budoucí použití, zabezpečte je přes `.htaccess` (již je to nastaveno).

### 2. Smazat testovací adresář

**DOPORUČENO:**
```
✗ tests/                   (celý adresář - není potřeba na produkci)
```

**Alternativa:** Zabezpečte přes `.htaccess` (již je to nastaveno).

### 3. Ověřit .htaccess

Zkontrolujte, že `.htaccess` obsahuje ochranu pro:
- `.env` soubor
- `tests/` adresář
- Migrační skripty

## 🔒 Bezpečnostní kontrola

### 1. Oprávnění souborů
Ověřte oprávnění (přes SSH nebo FTP):
```bash
.env: 600 (pouze vlastník)
*.php: 644 (čtení pro všechny, zápis pro vlastníka)
adresáře: 755
```

### 2. .env soubor
- ✅ Existuje na serveru
- ✅ Obsahuje správné produkční údaje
- ✅ Má oprávnění 600
- ✅ NENÍ přístupný přes web (test: https://efil.sensio.cz/.env → mělo by být 403)

### 3. PHP nastavení
Ověřte, že jsou nastavena:
- ✅ `log_errors: On`
- ✅ `session.cookie_httponly: On`
- ✅ `session.cookie_secure: On` (pokud máte HTTPS)
- ✅ `display_errors: Off`

## ✅ Funkční testování

### Základní funkce
- [ ] Přihlášení funguje (demo účet: demo@efil.cz / demo1234)
- [ ] Zobrazení seznamu filamentů
- [ ] Přidání nového filamentu
- [ ] Úprava filamentu
- [ ] Čerpání filamentu
- [ ] Přidání nového výrobce
- [ ] Správa cívek
- [ ] Statistiky se zobrazují

### API endpointy
Otevřete v prohlížeči (po přihlášení):
- [ ] `https://efil.sensio.cz/api/auth/me.php` → JSON s uživatelskými údaji
- [ ] `https://efil.sensio.cz/api/filaments/list.php` → JSON se seznamem filamentů
- [ ] `https://efil.sensio.cz/api/data/options.php` → JSON s možnostmi

### Bezpečnost
- [ ] `.env` není přístupný přes web (403 Forbidden)
- [ ] `tests/` není přístupný přes web (403 Forbidden)
- [ ] Migrační skripty nejsou přístupné přes web (403 Forbidden)

## 📝 Vytvoření produkčního účtu

### Varianta 1: Přes registraci
1. Otevřete aplikaci
2. Klikněte na "Registrace"
3. Vytvořte nový účet
4. Přihlaste se

### Varianta 2: Přes databázi (pokud máte přístup)
```sql
INSERT INTO users (email, password_hash, role)
VALUES (
    'vas-email@domena.cz',
    '$2y$10$...',  -- Heslo zahashované pomocí password_hash()
    'user'          -- nebo 'admin_efil' pro admin účet
);
```

**Generování hash hesla:**
```php
<?php
echo password_hash('vas-silne-heslo', PASSWORD_BCRYPT);
?>
```

## 🔄 Zálohování

### 1. Záloha databáze
- Vytvořte zálohu databáze přes phpMyAdmin nebo hosting panel
- Uložte zálohu na bezpečné místo
- Nastavte pravidelnou automatickou zálohu (pokud hosting podporuje)

### 2. Záloha souborů
- Zálohujte `.env` soubor (bezpečně, šifrovaně)
- Zálohujte celou aplikaci (přes FTP nebo hosting panel)

## 📊 Monitoring

### 1. PHP Error Log
- Pravidelně kontrolujte error log v adresáři `data/`
- Promazávejte log, aby nedošlo k vyčerpání diskového prostoru

### 2. Databázové statistiky
- Sledujte velikost databáze
- Kontrolujte výkon dotazů

## 🎉 Dokončeno!

Pokud jste prošli všechny kroky, aplikace je úspěšně nasazena a zabezpečena.

**Důležité kontakty/údaje:**
- URL: https://efil.sensio.cz
- Demo účet: demo@efil.cz / demo1234
- Databáze: sensiocz03
- Error log: data/error_log (nebo podobný název)

## 🆘 V případě problémů

1. Zkontrolujte PHP error log v `data/`
2. Ověřte, že všechny soubory jsou na místě
3. Zkontrolujte oprávnění souborů
4. Ověřte připojení k databázi
5. Kontaktujte podporu hostingu, pokud je problém na jejich straně

