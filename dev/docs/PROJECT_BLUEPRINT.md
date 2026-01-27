Projekt eFil: Profesionální Evidence Filamentů
1. Vize a Cíl
Aplikace pro komplexní správu 3D tiskových materiálů (SaaS model). Cílem je poskytnout uživatelům (jednotlivcům i firmám) přesný přehled o stavu zásob na základě reálného čerpání, nikoliv jen odhadů.
2. Technický Stack
 * Backend: PHP 8.x (čisté PDO pro MariaDB).
 * Frontend: HTML5, Tailwind CSS, Vanilla JavaScript (ES6+).
 * Komunikace: AJAX (Fetch API) pro asynchronní operace.
 * Konfigurace: .env soubor pro citlivé údaje a DB credentials.
 * Zabezpečení: Password hash (bcrypt), Session management, ošetření vstupů.
 * Architektura: Separation of Concerns (oddělené .css, .js, .php soubory).
3. Databázové Schéma
users
 * id (PK), email, password_hash, role (user, admin_efil), created_at
 * Pozn.: admin_efil má právo přepínat se do všech evidencí a spravovat uživatele.
inventories
 * id (PK), owner_id (FK users), name (např. "Hlavní dílna"), is_demo (bool)
inventory_access
 * id (PK), inventory_id (FK), access_code (unique hash pro URL), permission (read, write, manage)
 * Pozn.: Pozvaný uživatel se může do evidence dostat zadáním kódu nebo přes přímý odkaz.
spool_library (Komunitní DB)
 * id (PK), manufacturer_id (FK), weight_grams (int), visual_description (např. "Průhledná, 5 otvorů, průměr 200mm"), created_by (FK user)
filaments (Katalog cívek)
 * id (PK), inventory_id (FK), user_display_id (int - řada od 1 pro každou evidenci, manuálně editovatelné)
 * material, manufacturer, color_name, color_hex
 * spool_type_id (FK spool_library)
 * initial_weight_grams (int - hmotnost čistého materiálu při pořízení)
 * price (int), purchase_date (date), seller, location (text)
consumption_log (Kniha čerpání)
 * id (PK), filament_id (FK), amount_grams (int - záporná hodnota pro tisk, kladná pro inventurní opravu)
 * description (např. "Projekt: Váza"), created_at
4. Business Logika
Systém "Ledger" (Účetní kniha)
 * Zůstatek se nikdy neukládá jako statické číslo.
 * Aktuální zůstatek (Netto) = initial_weight_grams + SUM(consumption_log.amount_grams).
 * Hmotnost při vážení (Brutto) = Aktuální zůstatek + spool_library.weight_grams.
Navigační a filtrační strom
 * MAT (Materiál) | BAR (Barva) | VÝR (Výrobce/Detail)
 * Výchozí krok je variabilní (lze začít výběrem barvy i materiálu).
 * Řazení: Karty jsou vždy řazeny sestupně podle celkové hmotnosti (kg) v dané kategorii.
Práce s barvou
 * Color picker je ve formuláři na prvním místě.
 * Při výběru v paletě se textové pole color_name automaticky předvyplní nejbližším českým názvem (např. "Červená"). Uživatel název upraví jen při specifických odstínech (např. "Galaxy Red").
Správa cívek (Tára)
 * Uživatel identifikuje prázdnou cívku výběrem z knihovny (cca 20 standardních typů).
 * Knihovna je provázána s výrobci.
5. UI/UX Standardy
 * Přístupnost: Základní písmo 16px, dotykové cíle min. 48x48 dp.
 * Lokalizace: V UI používat desetinnou čárku a mezeru před jednotkou (např. 1,2 kg).
 * Karty: Čtvercový formát, text vycentrovaný, hmotnost v pravém horním rohu.
 * Top Menu: Rozbalovací menu shora pod hlavičkou pro akce (Přidat, Hledat, Statistiky).
 * Statistiky: Celková hmotnost skladu, celková investice (cena), čerpání za poslední týden/měsíc.
6. TODO List & Harmonogram
Fáze 0: Příprava (AI Kontext)
 * [x] Vytvořit soubor .env a config.php pro DB spojení.
 * [x] Vytvořit SQL migrační skript pro inicializaci MariaDB.
Fáze 1: Core Backend & Auth
 * [x] Implementovat registraci, login a systém rolí.
 * [x] Vytvořit "God mode" pro Admin eFil účet.
 * [x] Realizovat systém přístupových kódů (hashes) pro sdílení evidencí.
Fáze 2: Rozhraní a Evidence (AJAX)
 * [x] Implementovat "Chytré Selecty" s možností přidání nové hodnoty (MAT, VÝR, LOC).
 * [x] Vytvořit filtr (Wizard) MAT -> BAR -> VÝR se synchronizací filtrů.
 * [x] Implementovat logiku předvyplňování názvu barvy z hex kódu.
Fáze 3: Čerpání a Hmotnost
 * [x] Vytvořit modul "Čerpání" (klik na hmotnost -> modal pro zápis gramů).
 * [x] Implementovat komunitní knihovnu cívek (Spool Library).
 * [x] Integrovat automatický přepočet Netto/Brutto váhy.
Fáze 4: Dashboard a Demo
 * [x] Vytvořit sekci Statistiky s agregovanými daty z DB.
 * [x] Implementovat logiku Demo účtu pro neregistrované návštěvníky.
Fáze 5: Optimalizace
 * [x] Refaktorizace CSS do proměnných.
 * [x] Vytvořit testovací skripty pro validaci matematických výpočtů zůstatků.
