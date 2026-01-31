# eFil - Upgrade Guide na verzi 2.0

## 📋 Přehled změn

Verze 2.0 přináší významná vylepšení v oblasti správy uživatelů, zabezpečení a uživatelského rozhraní.

## 🔄 Postup upgradu

### 1. Zálohování dat

**DŮLEŽITÉ:** Před upgradem vždy zálohujte databázi!

```bash
mysqldump -u root -p efil_db > efil_backup_$(date +%Y%m%d).sql
```

### 2. Aktualizace souborů

Stáhněte nové soubory z repozitáře:

```bash
git pull origin main
```

### 3. Aktualizace databáze

Spusťte migrační skripty v pořadí podle potřeby.

**Výrobci (verzování)** – pokud máte staré schéma (sloupec `filaments.manufacturer` VARCHAR):
```bash
php dev/sql/migrate_manufacturers_versioned.php
```
Před spuštěním záloha DB. Při předchozím selhání migrace lze skript spustit znovu (resume). Viz [MANUFACTURERS.md](MANUFACTURERS.md).

**Čerpání a další sloupce:**
```bash
php dev/sql/update_consumption_schema.php
```

Tento skript přidá mj.:
- `consumption_date` - datum čerpání
- `created_by` - ID uživatele, který vytvořil záznam

### 4. Konfigurace emailů

Vytvořte `.env` soubor (pokud neexistuje) a přidejte:

```env
# Existing config
DB_HOST=localhost
DB_NAME=efil_db
DB_USER=root
DB_PASS=
APP_ENV=development

# NEW: JWT Secret for password reset
JWT_SECRET=vygenerujte-nahodny-retezec-min-32-znaku

# NEW: SMTP Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=vas-email@gmail.com
SMTP_PASSWORD=vase-heslo-nebo-app-password
SMTP_FROM_EMAIL=noreply@efil.cz
SMTP_FROM_NAME=eFil - Evidence Filamentů
```

**Generování JWT secret:**
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### 5. Test funkčnosti

1. Přihlaste se do aplikace
2. Ověřte, že data jsou zachována
3. Vyzkoušejte nové funkce:
   - Změna hesla v "Můj účet"
   - Navigace zpět/vpřed v prohlížeči

## 🆕 Nové funkce

### Správa účtu
- Menu → Můj účet
- Změna hesla
- Změna emailu
- Smazání účtu

### Zapomenuté heslo
- Na přihlašovací stránce: "Zapomněli jste heslo?"
- Zadejte email → Přijde odkaz s platností 1 hodina

### Demo režim
- Demo účet (`demo@efil.cz`) je nyní read-only
- Administrátor může editovat demo evidenci

### UI vylepšení
- Větší tlačítko pro zápis čerpání
- Umístění zobrazeno před ID filamentu
- Prázdné filamenty automaticky skryty
- Možnost smazat filament

### Routování
- Tlačítka Zpět/Vpřed v prohlížeči fungují
- Každá stránka má svou URL

## ⚠️ Breaking Changes

### Databáze
- Tabulka `consumption_log` má nové sloupce
- Starší záznamy mají `consumption_date` = datum vytvoření
- Starší záznamy nemají `created_by` (NULL)

### API
- API endpointy nyní kontrolují demo režim
- `/api/filaments/save.php` - vrací 403 v demo režimu
- `/api/filaments/consume.php` - vrací 403 v demo režimu
- `/api/filaments/delete.php` - vrací 403 v demo režimu

### Frontend
- Odstraněna zelená tečka (sync indicator)
- Změněno zobrazení hmotnosti v seznamu filamentů

## 🐛 Opravy chyb

- **Materiály a výrobci** - nyní pouze z databáze (odstraněny hardcoded seznamy)
- **Barevná paleta** - jednotná paleta pro demo i uživatele
- **Demo data** - opraveny české názvy barev

## 📚 Dokumentace

- `CHANGELOG.md` - Detailní seznam změn
- `README.md` - Aktualizovaná dokumentace
- `PROJECT_BLUEPRINT.md` - Technická dokumentace

## 🆘 Řešení problémů

### Email se neodesílá
1. Zkontrolujte SMTP údaje v `.env`
2. Pro Gmail použijte "App Password" místo běžného hesla
3. Zkontrolujte firewall a port 587

### Chyba při migraci databáze
1. Zkontrolujte, že skript běží s dostatečnými oprávněními
2. Ověřte připojení k databázi
3. Zkontrolujte logy: `tail -f /var/log/apache2/error.log`

### Nelze se přihlásit po upgradu
1. Vyčistěte cache prohlížeče
2. Zkontrolujte, že `config.php` má správné údaje
3. Ověřte, že databáze běží

## 📞 Podpora

Při problémech kontaktujte: podpora@sensio.cz
