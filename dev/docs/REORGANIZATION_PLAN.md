# Plán reorganizace struktury projektu

## Cíl
Přesunout všechny vývojové soubory do `dev/` a jeho podsložek, aby v rootu zůstaly pouze produkční soubory.

**Stav:** Reorganizace byla provedena. Testy jsou v `dev/tests/`, SQL a migrace v `dev/sql/`, skripty v `dev/scripts/`, dokumentace v `dev/docs/`.

## Aktuální vs. Cílový stav

### Root (produkční soubory - zůstávají)
- ✅ `index.php`
- ✅ `.htaccess`
- ✅ `.gitignore`
- ✅ `README.md`
- ✅ `config.php`
- ✅ `.env.example`
- ✅ `assets/`
- ✅ `api/`

### Přesuny

#### 1. Testy: `tests/` → `dev/tests/`
- `tests/balance_test.php`
- `tests/consumption_history_test.php`
- `tests/form_edit_data_load_test.php`
- `tests/form_persistence_test.php`
- `tests/grouping_test.php`
- `tests/helpers.php`
- `tests/manufacturer_auto_create_test.php`
- `tests/multiuser_test.php`
- `tests/options_optgroups_test.php`
- `tests/run_all_tests.php`
- `tests/spool_management_test.php`
- `tests/spool_manufacturer_test.php`
- `tests/user_display_id_test.php`
- `tests/MANUAL_TESTS.md`

#### 2. SQL schémata a migrace: `database/` + migrační skripty → `dev/sql/`
- `database/schema.sql` → `dev/sql/schema.sql`
- `init_db.php` → `dev/sql/init_db.php`
- `fix_demo_inventory.php` → `dev/sql/fix_demo_inventory.php`
- `check_demo_inventory.php` → `dev/sql/check_demo_inventory.php`
- `fix_all_schema.php` → `dev/sql/fix_all_schema.php`
- `update_consumption_log_schema.php` → `dev/sql/update_consumption_log_schema.php`
- `update_consumption_schema.php` → `dev/sql/update_consumption_schema.php`
- `update_current_weight.php` → `dev/sql/update_current_weight.php`
- `update_schema.php` → `dev/sql/update_schema.php`
- `update_spool_manufacturer_schema.php` → `dev/sql/update_spool_manufacturer_schema.php`
- `update_spool_schema.php` → `dev/sql/update_spool_schema.php`

#### 3. Testovací skripty: root → `dev/scripts/`
- `test_connection.php` → `dev/scripts/test_connection.php`
- `test_email.php` → `dev/scripts/test_email.php`

#### 4. Dokumentace: root → `dev/docs/`
- `CHANGELOG.md` → `dev/docs/CHANGELOG.md`
- `DEPLOYMENT_CHECKLIST.md` → `dev/docs/DEPLOYMENT_CHECKLIST.md`
- `DEPLOYMENT.md` → `dev/docs/DEPLOYMENT.md`
- `EMAIL_SETUP.md` → `dev/docs/EMAIL_SETUP.md`
- `PHP_SETTINGS.md` → `dev/docs/PHP_SETTINGS.md`
- `POST_DEPLOYMENT.md` → `dev/docs/POST_DEPLOYMENT.md`
- `PROJECT_BLUEPRINT.md` → `dev/docs/PROJECT_BLUEPRINT.md`
- `TROUBLESHOOTING.md` → `dev/docs/TROUBLESHOOTING.md`
- `UPGRADE.md` → `dev/docs/UPGRADE.md`

## Úpravy po přesunu

### 1. Cesty v PHP souborech
- `init_db.php`: Změnit `__DIR__ . '/database/schema.sql'` → `__DIR__ . '/schema.sql'` (už bude ve stejné složce)
- `run_all_tests.php`: Cesty k testům zůstanou relativní (bude ve `dev/tests/`)

### 2. Cesty v dokumentaci
- `README.md`: Aktualizovat cesty k migračním skriptům:
  - `php init_db.php` → `php dev/sql/init_db.php`
  - `php update_schema.php` → `php dev/sql/update_schema.php`
  - `http://localhost/a/efil-github/init_db.php` → `http://localhost/a/efil-github/dev/sql/init_db.php`

### 3. `.htaccess`
- Ujistit se, že `dev/` je blokován na produkci (pokud není localhost)
- Aktuálně je v `.htaccess` blokace `tests/`, po přesunu bude `dev/tests/` automaticky blokováno

### 4. `.gitignore`
- Zkontrolovat, že `dev/` není ignorován (mělo by být commitováno)

## Postup reorganizace

1. Vytvořit cílové složky: `dev/tests/`, `dev/sql/`, `dev/scripts/`
2. Přesunout soubory podle plánu
3. Aktualizovat cesty v PHP souborech
4. Aktualizovat dokumentaci (`README.md`, odkazy v `dev/docs/`)
5. Otestovat, že vše funguje
6. Commit změn

## Výhody

- ✅ Root obsahuje pouze produkční soubory
- ✅ Jasné oddělení vývojových a produkčních souborů
- ✅ Snadnější nasazení (stačí ignorovat `dev/`)
- ✅ Lepší organizace projektu
- ✅ Soulad s `.cursorrules`
