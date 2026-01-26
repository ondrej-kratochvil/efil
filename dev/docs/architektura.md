# Architektura eFil

## 📐 Přehled architektury

eFil je **full-stack webová aplikace** postavená na Vanilla Stacku:
- **Backend**: PHP 8.x s REST API
- **Frontend**: Vanilla JavaScript (ES6+), HTML5, Tailwind CSS
- **Databáze**: MySQL/MariaDB s InnoDB engine
- **Komunikace**: AJAX (Fetch API), JSON

---

## 🗄️ Databázové schéma

### Hlavní tabulky

#### `users`
Uživatelé systému.
- `id` - Primární klíč
- `email` - Email (unikátní)
- `password_hash` - Bcrypt hash hesla
- `role` - ENUM('user', 'admin_efil')

#### `inventories`
Evidence skladů.
- `id` - Primární klíč
- `owner_id` - Vlastník (FK → users)
- `name` - Název evidence
- `is_demo` - Boolean flag pro demo režim

#### `inventory_members`
Členové sdílených evidencí (M:N vztah).
- `inventory_id` - FK → inventories
- `user_id` - FK → users
- `role` - ENUM('read', 'write', 'manage')
- UNIQUE(inventory_id, user_id)

#### `inventory_access`
Přístupové kódy pro sdílení.
- `inventory_id` - FK → inventories
- `access_code` - Unikátní hash kód
- `permission` - ENUM('read', 'write', 'manage')

#### `filaments`
Filamenty ve skladu.
- `id` - Primární klíč
- `inventory_id` - FK → inventories
- `user_display_id` - Uživatelsky nastavitelné číslo (#1, #2, ...)
- `material` - Typ materiálu (PLA, PETG, atd.)
- `manufacturer` - Výrobce
- `color_name` - Název barvy
- `color_hex` - Hex kód barvy
- `spool_type_id` - FK → spool_library (volitelné)
- `initial_weight_grams` - Počáteční hmotnost
- `price` - Cena (volitelné)
- `purchase_date` - Datum nákupu
- `seller` - Prodejce
- `location` - Umístění

#### `consumption_log`
Záznamy spotřeby.
- `id` - Primární klíč
- `filament_id` - FK → filaments
- `amount_grams` - Množství (záporné = čerpání, kladné = korekce)
- `description` - Poznámka
- `consumption_date` - Datum spotřeby
- `created_by` - FK → users (kdo vytvořil záznam)

#### `spool_library`
Knihovna typů cívek.
- `id` - Primární klíč
- `weight_grams` - Hmotnost cívky (tára)
- `color` - Barva cívky
- `material` - Materiál cívky
- `outer_diameter_mm` - Vnější průměr
- `width_mm` - Šířka
- `visual_description` - Popis
- `created_by` - FK → users (NULL = standardní cívka)

#### `spool_manufacturer`
Vazební tabulka M:N mezi cívkami a výrobci.
- `spool_id` - FK → spool_library
- `manufacturer_id` - FK → manufacturers
- UNIQUE(spool_id, manufacturer_id)

#### `manufacturers`
Výrobci.
- `id` - Primární klíč
- `name` - Název výrobce (unikátní)

### Vztahy

```
users (1) ──< inventories (owner_id)
inventories (1) ──< inventory_members (M:N)
inventories (1) ──< filaments
filaments (1) ──< consumption_log
filaments (N) ──> spool_library (spool_type_id)
spool_library (M) ──< spool_manufacturer (M:N) ──> manufacturers
```

### Indexy a optimalizace

- `users.email` - UNIQUE index (prefix 191 znaků kvůli utf8mb4)
- `inventory_members(inventory_id, user_id)` - UNIQUE index
- `spool_manufacturer(spool_id, manufacturer_id)` - UNIQUE index
- `ROW_FORMAT=DYNAMIC` - Pro podporu velkých TEXT polí
- Prefix indexy pro VARCHAR(255) polí kvůli limitům InnoDB

---

## 🔌 API Architektura

### Struktura endpointů

Všechny API endpointy jsou v `/api/` a vracejí JSON.

#### Autentizace
- `POST /api/auth/login.php` - Přihlášení (nastaví session)
- `POST /api/auth/register.php` - Registrace
- `GET /api/auth/logout.php` - Odhlášení (zruší session)
- `GET /api/auth/me.php` - Informace o přihlášeném uživateli
- `POST /api/auth/forgot-password.php` - Zapomenuté heslo (JWT token)
- `POST /api/auth/reset-password.php` - Reset hesla (ověření JWT)

#### Filamenty
- `GET /api/filaments/list.php` - Seznam filamentů s aktuální hmotností
- `POST /api/filaments/save.php` - Uložení/úprava filamentu
- `POST /api/filaments/consume.php` - Záznam spotřeby
- `POST /api/filaments/delete.php` - Smazání filamentu

#### Čerpání
- `GET /api/consumption/list.php` - Historie čerpání (filtr podle filament_id)
- `GET /api/consumption/get.php` - Detail jednoho záznamu
- `POST /api/consumption/update.php` - Úprava záznamu
- `POST /api/consumption/delete.php` - Smazání záznamu

#### Data
- `GET /api/data/options.php` - Možnosti pro selecty (materiály, výrobci) s optgroups

#### Cívky
- `GET /api/spools/list.php` - Seznam typů cívek s vazbami na výrobce
- `POST /api/spools/create.php` - Vytvoření nového typu cívky
- `POST /api/spools/update.php` - Úprava typu cívky
- `POST /api/spools/delete.php` - Smazání typu cívky

#### Evidence
- `GET /api/inventory/list.php` - Seznam evidencí uživatele
- `POST /api/inventory/switch.php` - Přepnutí aktivní evidence (nastaví session)
- `POST /api/inventory/share.php` - Vygenerování sdílecího kódu
- `POST /api/inventory/join.php` - Připojení k evidenci pomocí kódu

#### Uživatelé
- `GET /api/users/list.php` - Seznam uživatelů v evidenci
- `POST /api/users/add.php` - Přidání uživatele (email nebo kód)
- `POST /api/users/update-role.php` - Změna role uživatele
- `POST /api/users/remove.php` - Odebrání uživatele

#### Účet
- `POST /api/account/change-password.php` - Změna hesla
- `POST /api/account/change-email.php` - Změna emailu
- `POST /api/account/delete.php` - Smazání účtu

#### Statistiky
- `GET /api/dashboard/stats.php` - Statistiky skladu
- `GET /api/admin/stats.php` - Celkové statistiky eFil (pouze admin_efil)

### Session Management

- PHP sessions pro autentizaci
- `$_SESSION['user_id']` - ID přihlášeného uživatele
- `$_SESSION['inventory_id']` - Aktivní evidence
- Session cookie s `httponly` flagem

### Error Handling

- Všechny endpointy vracejí JSON
- HTTP status kódy: 200 (OK), 400 (Bad Request), 401 (Unauthorized), 403 (Forbidden), 404 (Not Found), 500 (Server Error)
- Chybové zprávy v češtině pro uživatele

---

## 🎨 Frontend Architektura

### Struktura

- **SPA (Single Page Application)** s History API routováním
- **Vanilla JavaScript** (ES6+ moduly)
- **Tailwind CSS** pro styling
- **Bez frameworků** - čistý Vanilla Stack

### Routování

Aplikace používá **History API** pro podporu tlačítek Zpět/Vpřed:

```javascript
// Routes
/wizard/mat      → Krok 1: Výběr materiálu
/wizard/bar      → Krok 2: Výběr barvy
/wizard/vyr      → Krok 3: Detail filamentů
/form            → Formulář pro přidání/úpravu
/form/:id        → Úprava konkrétního filamentu
/consume/:id     → Záznam spotřeby
/stats           → Statistiky
/help            → Nápověda
/account         → Správa účtu
/users           → Správa uživatelů
/spools          → Knihovna cívek
/admin-stats     → Admin statistiky
/inventory-switch → Přepínání evidencí
```

### State Management

Globální state objekt:
```javascript
state = {
    view: 'wizard',           // Aktuální view
    authView: 'login',        // Auth sub-view
    currentStep: 1,           // Wizard krok
    filters: { mat: null, color: null },
    editingId: null,          // ID editovaného filamentu
    consumeId: null,          // ID filamentu pro čerpání
    consumeMode: 'used',      // 'used' nebo 'weight'
    formFieldsStatus: {},     // Select/input módy pro formuláře
    expandedGroups: new Set() // Rozbalené skupiny filamentů
}
```

### Data Flow

1. **Načtení dat**: `loadData()` → Fetch API → Backend
2. **Render**: `render()` → Aktualizace DOM podle `state`
3. **User Action**: Event handler → Update `state` → `render()`
4. **API Call**: Fetch → Backend → Update lokální data → `render()`

### Base Path Detection

Aplikace automaticky detekuje base path pro subdirectory instalace:
- `/a/efil-github/` → base path = `/a/efil-github`
- `/` → base path = `` (root instalace)

Logika je v `index.html` (synchronní) a `app.js` (asynchronní).

---

## 🔐 Bezpečnost

### Autentizace
- **Bcrypt** pro hashování hesel
- **PHP Sessions** pro autentizaci
- **JWT tokeny** pro reset hesla (1 hodina platnost)

### Autorizace
- Kontrola oprávnění na každém endpointu
- Role-based access control (read/write/manage/owner)
- Demo režim (read-only, kromě admin_efil)

### SQL Injection
- **PDO Prepared Statements** výhradně
- Pojmenované parametry
- `ATTR_EMULATE_PREPARES = false`

### XSS
- Escapování výstupu v PHP (`htmlspecialchars`)
- Frontend: bezpečné manipulace s DOM

### CSRF
- Session-based autentizace (implicitní ochrana)
- JWT tokeny pro reset hesla (time-limited)

---

## 📦 Konfigurace

### Environment Variables (`.env`)

```env
DB_HOST=localhost
DB_NAME=efil_db
DB_USER=root
DB_PASS=

JWT_SECRET=your-secret-key
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_FROM_EMAIL=noreply@efil.cz
SMTP_FROM_NAME=eFil - Evidence Filamentů
```

### Config.php

- Načítá `.env` soubor
- Vytváří PDO připojení
- Poskytuje helper funkce (`getBaseUrl()`, `getFullBaseUrl()`)

---

## 🚀 Deployment

### Požadavky
- PHP 8.0+ s PDO rozšířením
- MySQL/MariaDB 5.7+
- Webový server (Apache/Nginx)
- SMTP server (volitelné, pro e-maily)

### Struktura na serveru
- Root: `index.html`, `config.php`, `.env`
- `/api/` - Backend endpointy
- `/assets/` - CSS, JS, obrázky
- `/database/` - SQL schémata (nenasazuje se)
- `/dev/` - Dokumentace a testy (nenasazuje se)

### Bezpečnostní opatření
- `.env` soubor mimo web root nebo s oprávněními 600
- `init_db.php` smazat nebo zabezpečit přes `.htaccess`
- Testy a dokumentace nekopírovat na produkci
