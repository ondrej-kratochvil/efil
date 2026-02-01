# eFil - Seznam změn

## Verze 2.0 - Multiuser a rozšířené funkce

### 📐 DRY a centralizace výpočtů (leden 2026)
- **Průměrná cena za kg** – jeden výpočet v pomocné funkci `getAvgCzkPerKg()` v `assets/js/utils.js`; volá se na kartách MAT, BAR a VÝR (skupiny i jednotlivé položky). Žádný duplicitní kód.
- **Pravidlo v `.cursorrules`** – znovupoužitelnost a DRY: vytvářet funkce, které se volají na všech místech, ne kopírovat logiku.

### ✅ Implementované funkce

#### 🔐 Správa uživatelů a účtů
- **Zapomenuté heslo** - kompletní flow s JWT tokeny a emailovými notifikacemi
- **Správa účtu** - změna hesla, emailu, smazání účtu
- **API pro správu uživatelů** - přidání, odebrání, změna oprávnění (read/write/manage)
- **Email notifikace** - automatické emaily při:
  - Vytvoření nového účtu
  - Pozvání do evidence
  - Změně oprávnění
  - Odebrání z evidence

#### 🔧 Technické vylepšení
- **History API routování** - navigace zpět/vpřed v prohlížeči funguje správně
- **Rozšířené DB schéma**:
  - Datum čerpání (`consumption_date`)
  - Autor čerpání (`created_by`)
  - JWT secret pro tokeny
  - SMTP konfigurace

#### 🎨 UI vylepšení
- **Větší tlačítko hmotnosti** - snadnější klikání na zápis čerpání
- **Umístění před ID** - lepší přehlednost (např. "Polička A | #1")
- **Odstraněna zelená tečka** - jednodušší UI
- **Odstraněn text "Zůstatek"** - více prostoru pro hmotnost

#### 📊 Data a zobrazení
- **Prázdné filamenty skryty** - automaticky se nezobrazují filamenty s nulovou hmotností
- **Tlačítko Smazat** - v editaci filamentu s potvrzením
- **Demo režim read-only** - demo evidence nelze editovat (kromě admin_efil)
- **Materiály a výrobci pouze z DB** - odstraněny hardcoded seznamy
- **Jednotná barevná paleta** - demo i uživatelské rozhraní používají stejné barvy

#### 📈 Statistiky pro uživatele (dashboard)
- **API dashboard/stats.php** – data vždy pro aktivně vybranou evidenci (`getInventoryIdForUser`); celková hmotnost, odhad hodnoty, počet cívek, spotřeba za 30 dní
- **Rozložení materiálů** – koláčový graf (zbývající hmotnost podle materiálu), paleta odlišných barev
- **Historie čerpání** – sloupcový graf spotřeby po dnech (30 dní) nad tabulkou záznamů
- **Oprava** – v dotazu pro celkovou hmotnost/hodnotu/počet se používala proměnná `$invId` místo `$inventoryId` (nulové hodnoty)

#### 🏭 Verzování výrobců (manufacturers)
- **Verzovaná tabulka výrobců** – soft delete, `invalidated_at` / `invalidated_by` (kdo smazal)
- **API výrobců** – list, create, update, delete; admin: pending, approve, reject
- **Filamenty a typy cívek** – reference na logické `manufacturer_id`; formuláře posílají `man_id` nebo `man` (nový název)
- **Migrace** – `dev/sql/migrate_manufacturers_versioned.php` pro existující DB; podpora resume po částečném selhání
- Dokumentace: [SOFT_DELETE_AND_VERSIONING.md](SOFT_DELETE_AND_VERSIONING.md), [MANUFACTURERS.md](MANUFACTURERS.md)

#### 🎯 Typy cívek – validace a UX (leden 2026)
- **Barva a materiál povinné** – u vytvoření i úpravy typu cívky (API + formulář Správa typů cívek)
- **Klik na + u typu cívky** – jen rozbalí pole pro nový typ, hodnoty formuláře (materiál, barva, výrobce…) se nevymažou; barva a materiál typu cívky se předvyplní z filamentu
- **Výrobce filamentu → typ cívky** – při vytvoření nového typu cívky z formuláře filamentu se výrobce filamentu propisuje do typu cívky (`spools/save.php`, parametr `manufacturer`)
- **Testy** – Spool Management Tests přepsány na tabulku `spool_types` a logické `spool_type_id`

### 📋 Zbývající úkoly (plánováno)

#### Vysoká priorita
- [ ] UI pro správu uživatelů v menu
- [ ] Historie čerpání s editací
- [ ] Groupování cívek (více cívek stejné barvy)
- [ ] Přepínání mezi evidencemi

#### Střední priorita
- [ ] Správa typů cívek s vazbami na výrobce (M:N)
- [ ] Představení aplikace na přihlašovací stránce
- [ ] Podrobná nápověda
- [ ] Statistiky eFil pro admina (platformové)

### 🔒 Bezpečnost
- JWT tokeny pro reset hesla (1 hodina platnost)
- Password setup tokeny (24 hodin platnost)
- SMTP konfigurace v .env
- Demo režim read-only

### 📧 SMTP konfigurace

Přidejte do `.env`:
```env
JWT_SECRET=your-secret-key-change-this-in-production
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_FROM_EMAIL=noreply@efil.cz
SMTP_FROM_NAME=eFil - Evidence Filamentů
```

### 🗄️ Databázové změny

- **Výrobci (verzování):** Pro existující DB se starým schématem výrobců spusťte jednou:
  ```bash
  php dev/sql/migrate_manufacturers_versioned.php
  ```
  Před spuštěním záloha DB. Při předchozím selhání migrace lze skript spustit znovu (resume).

Ostatní migrace pro existující databáze:
```bash
php dev/sql/update_consumption_schema.php
```

Nové instalace:
```bash
php dev/sql/init_db.php
```

### 📝 Poznámky pro vývojáře
- Všechny routy jsou nyní v History API
- Email systém používá PHP mail() (pro produkci doporučujeme SMTP službu)
- JWT tokeny jsou signed s tajným klíčem z konfigurace
- Demo evidence má `is_demo = 1` v tabulce inventories
