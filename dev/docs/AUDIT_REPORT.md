# Audit Report - eFil Project

**Datum:** 2026-02-04  
**Auditor:** AI Assistant (AUDIT podle .cursorrules)  
**Verze pravidel:** .cursorrules (Vanilla Stack)

---

## Shrnutí

| Kategorie | Status | Poznámky |
|-----------|--------|----------|
| **Struktura projektu** | Vyhovuje | Root čistý, vývoj v `dev/`; v rootu `package.json` a `tailwind.config.js` (build – akceptovatelné) |
| **PHP standardy** | Vyhovuje | Všechny API soubory + config.php mají `declare(strict_types=1)` |
| **SQL – API** | Vyhovuje | Žádné `SELECT *` v produkčním API; prepared statements |
| **SQL – výjimky** | Drobný dluh | 2× `$pdo->query()` bez parametrů (getNextSpoolTypeId, getNextManufacturerId) – bezpečné, ale předpis preferuje prepared statements |
| **UI/UX – Header, Favicon** | Vyhovuje | Logo SVG, favicon, hamburger menu |
| **UI/UX – Footer** | Vyhovuje | © [rok] Sensio.cz s.r.o. s odkazem na https://sensio.cz (index.php) |
| **UI/UX – Homepage úvod** | Částečně | Úvod na přihlašovací stránce (auth); chybí **skrývatelnost** a ukládání do localStorage; na wizardu (po přihlášení) úvod není |
| **UI/UX – Menu** | Vyhovuje | Evidence / Nastavení ve stromu (details), pod 7 položek první úrovně |
| **Light/Dark mode** | Vyhovuje | `prefers-color-scheme`, přepínač v menu, uložení do `localStorage` (efil-theme) |
| **Přístupnost (a11y)** | Vyhovuje | Prohlášení o přístupnosti v Nápovědě (help), odkaz v footeru; kontakt, úroveň WCAG |
| **Klávesové zkratky** | Částečně | F1 = Nápověda, Escape = zavření menu; v Nápovědě a prohlášení uvedeny; další zkratky (nový záznam, přepnutí evidence) chybí |
| **Dokumentace** | Vyhovuje | `dev/docs/` včetně main.md, architektury, VERIFY_CHECKLIST |
| **Délka souborů** | Vyhovuje | app.js ~481 ř., main.css ~223 ř. (limity 600 / 800) |
| **Výkon** | Vyhovuje | Cache-busting CSS/JS, moduly (defer), žádný N+1 v API |

---

## Dodržené standardy

### 1. Struktura projektu
- Root: `index.php`, `.htaccess`, `.gitignore`, `README.md`, `.env.example`, `config.php`, `api/`, `assets/`, `package.json`, `tailwind.config.js`.
- Vývoj v `dev/`: docs, tests, scripts, sql.

### 2. PHP a backend
- **Strict types:** Všechny soubory v `api/` a `config.php` mají `declare(strict_types=1);`.
- **PDO:** Převážně prepared statements.
- **SELECT *:** V produkčním API se nevyskytuje. V `dev/tests/consumption_history_test.php` jsou 3× `SELECT *` – pouze testy.
- **API:** Endpointy podle domén (auth, filaments, consumption, inventory, users, spools, manufacturers, account, admin).

### 3. Frontend
- **JS:** Vanilla ES6+ moduly (app.js, router, api, state, config, utils, views/*).
- **Cache-busting:** `?v=<?= filemtime(...) ?>` pro CSS a JS v index.php.
- **Favicon:** `assets/img/favicon.svg`, dynamicky dle base path.

### 4. UI/UX
- **Footer:** „© [rok] Sensio.cz s.r.o.“ s odkazem na https://sensio.cz.
- **Téma:** Inicializace z `localStorage` nebo `prefers-color-scheme`, přepínač v menu s `aria-label`.
- **Prohlášení o přístupnosti:** Sekce v Nápovědě (help.js), odkaz v footeru (`#footer-accessibility-link` → Nápověda + scroll na sekci).

### 5. Klávesové zkratky
- F1 → Nápověda (app.js keydown), Escape → zavření menu.
- V Nápovědě je tabulka zkratek a odkaz v prohlášení o přístupnosti.

---

## Nesoulady a technický dluh

### 1. Homepage – skrývatelný úvodní text (částečný soulad)

- **Předpis:** Na homepage stručný text o hlavních funkcích; sekce **skrývatelná** („Skrýt úvod“ / „Zobrazit úvod“); preference ukládaná do localStorage.
- **Stav:** Úvodní blok je na **přihlašovací stránce** (auth view) a popisuje funkce aplikace. **Chybí:** tlačítko „Skrýt úvod“, ukládání preference do localStorage. Na **wizardu** (homepage po přihlášení) žádný úvodní text.
- **Návrh:** (a) V auth view přidat tlačítko „Skrýt úvod“ / „Zobrazit úvod“ a ukládat např. `localStorage.setItem('efil-hide-intro', '1')`; při další návštěvě úvod podle preference skrýt. (b) Volitelně: krátký skrývatelný úvod i na wizardu s vlastní preferencí.

### 2. PDO `query()` místo prepared statements (nízká priorita)

- **Soubor:** `api/helpers/spool_types.php` – `getNextSpoolTypeId()`: `$pdo->query("SELECT COALESCE(MAX(spool_type_id), 0) + 1 FROM spool_types")`.
- **Soubor:** `api/helpers/manufacturers.php` – `getNextManufacturerId()`: `$pdo->query("SELECT COALESCE(MAX(manufacturer_id), 0) + 1 FROM manufacturers")`.
- **Riziko:** Žádné (žádné uživatelské vstupy). Předpis ale požaduje „výhradně prepared statements“.
- **Návrh:** Ponechat je možné; nebo převést na `$pdo->prepare(...)->execute()` pro konzistenci.

### 3. Klávesové zkratky – rozšíření (doporučení)

- **Předpis:** Nejpoužívanějším operacím přiřadit zkratky (nový záznam, vyhledávání, nápověda, přepnutí evidence).
- **Stav:** F1 a Escape jsou. Chybí např. zkratky pro „Přidat filament“, „Přehled skladu“, „Přepnout evidenci“.
- **Návrh:** Doplnit podle potřeby (např. Ctrl+N = nový filament) a uvést v Nápovědě a v prohlášení o přístupnosti.

### 4. Multijazyčnost (i18n)

- **Předpis:** Výchozí čeština + angličtina; nebo výjimka v projektu („pouze čeština“).
- **Stav:** Texty jsou natvrdo v kódu (česky). Není `assets/i18n/`, ani přepínač jazyka. V .cursorrules není výslovná výjimka pro „pouze čeština“.
- **Návrh:** Buď v .cursorrules uvést výjimku (např. „Aplikace pouze v češtině, interní použití“), nebo připravit i18n (cs/en JSON, t(key), přepínač, uložení jazyka).

### 5. Testy – SELECT * (nízká priorita)

- **Soubor:** `dev/tests/consumption_history_test.php` – 3× `SELECT * FROM consumption_log`.
- **Návrh:** V produkci se nepoužívá; v testech lze pro přehlednost ponechat nebo nahradit výčtem sloupců.

### 6. Mapa webu (sitemap)

- **Předpis:** U rozsáhlejšího webu mapa webu (statická stránka s odkazy nebo sitemap.xml).
- **Stav:** Chybí.
- **Návrh:** Pro SPA s jednou vstupní stránkou a client-side routami může stačit jedna stránka „Mapa webu“ v Nápovědě s odkazy na hlavní sekce (Evidence, Přehled, Účet, Nápověda, …). Případně sitemap.xml pro vyhledávače.

---

## Optimalizace výkonu

- **Cache-busting:** V index.php pro CSS a JS – vyhovuje.
- **Skripty:** Načítání jako modul (defer) – vyhovuje.
- **Databáze:** V kontrolovaných endpointech není N+1; JOINy a struktura dotazů v pořádku.

---

## Návrh úprav (prioritně)

### Střední priorita
1. **Úvod na přihlašovací stránce:** Přidat „Skrýt úvod“ / „Zobrazit úvod“ a ukládání preference do localStorage.
2. **i18n:** Buď výjimka v .cursorrules (pouze čeština), nebo příprava i18n (cs/en).

### Nízká priorita
3. **PDO:** Volitelně převést 2× `query()` v helperech na prepare/execute.
4. **Klávesové zkratky:** Rozšířit o hlavní akce a uvést v Nápovědě a prohlášení.
5. **Mapa webu:** Stránka/section v Nápovědě nebo sitemap.xml.
6. **Testy:** V consumption_history_test.php volitelně nahradit `SELECT *` výčtem sloupců.

---

## Závěr

Projekt **vysokou měrou dodržuje** .cursorrules: struktura, PHP strict types, SQL v API, dokumentace, footer, Light/Dark mode, prohlášení o přístupnosti a F1/Escape jsou v souladu. Zbývající body:

- **Skrývatelný úvod** na přihlašovací stránce (a volitelně na wizardu),
- **i18n** – buď výjimka, nebo příprava vícejazyčnosti,
- drobnosti: 2× PDO query v helperech, rozšíření zkratek, mapa webu.

**Celkové hodnocení:** cca 8,5/10 – velmi dobrý soulad s předpisem; několik doplnění dovede projekt k plnému souladu.
