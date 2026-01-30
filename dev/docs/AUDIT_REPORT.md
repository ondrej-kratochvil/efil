# Audit Report - eFil Project

**Datum:** 2026-01-30  
**Auditor:** AI Assistant  
**Verze pravidel:** .cursorrules (Vanilla Stack, včetně a11y, menu, homepage, footer)

---

## Shrnutí

| Kategorie | Status | Poznámky |
|-----------|--------|----------|
| **Struktura projektu** | Vyhovuje | Root čistý, vývoj v `dev/`; v rootu jsou `package.json` a `tailwind.config.js` (build) |
| **PHP standardy** | Vyhovuje | Všechny API soubory mají `declare(strict_types=1)`, PDO prepared statements |
| **SQL** | Vyhovuje | Žádné `SELECT *`, parametrizované dotazy |
| **UI/UX – Header, Favicon** | Vyhovuje | Logo SVG, favicon, hamburger menu |
| **UI/UX – Homepage úvod** | Částečně | Úvodní text je na přihlašovací stránce; na homepage (wizard) chybí skrývatelný úvod |
| **UI/UX – Footer** | Nesoulad | V index.php je „© 2026 eFil“; předpis: „© [rok] Sensio.cz s.r.o.“ + odkaz |
| **UI/UX – Menu** | Částečně | 7 položek OK; chybí seskupení do stromu (např. Nastavení → Účet, Cívky, Uživatelé) |
| **Light/Dark mode** | Chybí | Není `prefers-color-scheme` ani přepínač s uložením do localStorage |
| **Přístupnost (a11y)** | Částečně | Sémantika a formuláře v pořádku; chybí prohlášení o přístupnosti |
| **Klávesové zkratky** | Částečně | Pouze Escape (zavření menu); chybí zkratky pro hlavní akce a jejich uvedení v prohlášení |
| **Dokumentace** | Vyhovuje | `dev/docs/` kompletní, včetně main.md, architektury, roadmapy |
| **Výkon** | Vyhovuje | Cache-busting CSS/JS, skripty jako moduly (defer), žádný N+1 v dotazech |

---

## Dodržené standardy

### 1. Struktura projektu

- **Root obsahuje pouze nasazovatelné soubory:** `index.php`, `.htaccess`, `.gitignore`, `README.md`, `.env.example`, `config.php`, `api/`, `assets/`.
- **Vývojové soubory v `dev/`:**
  - `dev/docs/` – dokumentace (včetně main.md, BUILD.md, DEPLOYMENT.md, …)
  - `dev/tests/` – automatizované testy
  - `dev/scripts/` – diagnostika (test_connection.php, test_email.php)
  - `dev/sql/` – schémata a migrace
- **Poznámka:** V rootu jsou `package.json` a `tailwind.config.js` – slouží k buildu CSS. Předpis výslovně povoluje v rootu jen produkční soubory; build konfigurace je na hraně (běžná praxe je nechat je v rootu pro CI/build).

### 2. PHP a backend

- **Strict types:** Všechny relevantní PHP soubory v `api/` mají `declare(strict_types=1);`.
- **PDO:** Pouze prepared statements, žádná konkatenace SQL.
- **SELECT:** Žádné `SELECT *` v API.
- **API struktura:** Endpointy rozdělené podle domén (auth, filaments, consumption, inventory, users, spools, account, admin, …).

### 3. Frontend

- **JavaScript:** Vanilla ES6+ moduly (ESM), logika v `/assets/js` a `views/`.
- **Délka souborů:** Všechny kontrolované JS soubory pod 600 řádků (největší např. form.js ~403 řádků).
- **Načítání:** Hlavní skript jako `type="module"` (implicitně defer), cache-busting přes `?v=filemtime`.
- **Favicon:** Nastavená v `assets/img/favicon.svg`, v HTML dynamicky podle base path.

### 4. Dokumentace

- **`dev/docs/main.md`:** Přítomen jako rozcestník.
- **Ostatní:** Architektura, algoritmy, UI, deployment, BUILD, VERIFY_CHECKLIST, manuální testy, roadmapa – k dispozici.

---

## Nesoulady a technický dluh

### 1. Footer – copyright (nesoulad s předpisem)

- **Předpis:** „© [aktuální rok] [Sensio.cz s.r.o.]“ + odkaz na https://sensio.cz/
- **Stav:** V `index.php` je „© 2026 eFil - Evidence Filamentů“ bez Sensio.cz. Odkaz na Sensio.cz je v auth view a v help view, ne v hlavním footeru.
- **Návrh:** Upravit footer v `index.php` na formát dle předpisu (© rok, Sensio.cz s.r.o., odkaz). Případně sjednotit s auth/help (jedna značka v celé aplikaci).

### 2. Homepage – skrývatelný úvodní text (částečný soulad)

- **Předpis:** Na homepage musí být stručný text o hlavních funkcích (SEO + první návštěva). Sekce musí být skrývatelná; preference „skrýt úvod“ se ukládá (např. localStorage), aby opakovaný uživatel nemusel úvod přeskakovat.
- **Stav:** Úvodní blok s popisem funkcí je na **přihlašovací stránce** (auth view). Na **homepage po přihlášení** (wizard – MAT/BAR/VÝR) žádný úvodní text ani skrývání není.
- **Návrh:** Buď (a) doplnit na wizard view krátký úvodní blok (hlavní funkce, klíčová slova) se tlačítkem „Skrýt úvod“ / „Zobrazit úvod“ a ukládáním do `localStorage`, nebo (b) na přihlašovací stránce doplnit skrývání úvodu + localStorage, aby se úvod po dalších návštěvách znovu nezobrazoval. Ideálně obojí: úvod na login skrývatelný + úvod na wizard (po přihlášení) skrývatelný.

### 3. Light/Dark mode (chybí)

- **Předpis:** Výchozí téma dle `prefers-color-scheme`; manuální přepínač v menu s uložením (např. localStorage).
- **Stav:** Žádné použití `prefers-color-scheme`, žádný přepínač tématu.
- **Návrh:** Implementovat světlé/tmavé téma (CSS proměnné + media query + třída na `<html>`), přepínač v menu (nebo v účtu) a ukládat volbu do localStorage.

### 4. Prohlášení o přístupnosti (chybí)

- **Předpis:** Každá aplikace má prohlášení o přístupnosti (stránka nebo sekce v nápovědě/footeru). Uvést úroveň souladu, kontakt, datum revize.
- **Stav:** V aplikaci žádné prohlášení; zmínky jen v dokumentaci a v .cursorrules.
- **Návrh:** Přidat sekci „Přístupnost“ do Nápovědy (nebo samostatnou stránku / pododkaz v footeru) s textem: úroveň WCAG, kontakt pro připomínky, datum poslední revize.

### 5. Klávesové zkratky (částečně)

- **Předpis:** Nejpoužívanějším operacím přiřadit zkratky; uvést je v prohlášení o přístupnosti a v nápovědě/menu (např. „Nápověda (F1)“).
- **Stav:** Jediná globální zkratka je Escape (zavření action menu). Žádné zkratky pro Přidat filament, Přehled, Nápověda, Přepnutí evidence atd.
- **Návrh:** Definovat zkratky (např. F1 = Nápověda, Ctrl+N = nový filament, …), implementovat je v `keydown` a uvést v prohlášení o přístupnosti a v Nápovědě (např. tabulka zkratek).

### 6. Menu – stromová struktura (doporučení)

- **Předpis:** Menu stručné; max. 7 položek v první úrovni; podobné položky seskupit do stromu (dropdown).
- **Stav:** V action menu je 7 položek (Přidat, Přehled, Účet, Uživatelé, Cívky, Nápověda, Odhlásit) – počet vyhovuje, ale všechny jsou v jedné úrovni.
- **Návrh:** Seskupit např. „Nastavení“ → Účet, Správa uživatelů, Typy cívek; nebo „Evidence“ → Přidat filament, Přehled skladu. Tím zůstane první úroveň přehlednější a lépe se škáluje při dalších funkcích.

### 7. Tailwind CDN a autocomplete (střední priorita)

- **Tailwind:** Aplikace stále používá `https://cdn.tailwindcss.com`. Předpis a BUILD.md doporučují lokální build (Tailwind CLI / PostCSS).
- **Autocomplete:** U přihlašovacích a jiných formulářů doplnit vhodné `autocomplete` atributy (email, current-password, username), kde to dává smysl.

---

## Optimalizace výkonu

- **Cache-busting:** V index.php se pro CSS a JS používá `filemtime()` v query parametru – vyhovuje.
- **Skripty:** Načítání jako modul = defer – vyhovuje.
- **Databáze:** V kontrolovaných endpointech není cyklické dotazování (N+1); používání JOINů a jednoduchých dotazů je v pořádku.

---

## Návrh úprav (prioritně)

### Vysoká priorita (soulad s předpisem)

1. **Footer:** Upravit hlavní footer v `index.php` na „© [rok] Sensio.cz s.r.o.“ s odkazem na https://sensio.cz/

### Střední priorita

2. **Homepage úvod:** Na wizard (homepage po přihlášení) přidat krátký úvodní text (hlavní funkce + SEO) se skrýváním a ukládáním preference do localStorage.  
3. **Prohlášení o přístupnosti:** Přidat sekci/stránku s prohlášením (úroveň, kontakt, datum) a odkaz z Nápovědy nebo footeru.  
4. **Klávesové zkratky:** Zavedení zkratek pro hlavní akce a jejich uvedení v prohlášení a v Nápovědě.  
5. **Light/Dark mode:** Implementace podle předpisu (prefers-color-scheme + přepínač + localStorage).

### Nízká priorita

6. **Menu:** Seskupit položky do stromu (např. Nastavení / Evidence).  
7. **Tailwind:** Přesun z CDN na lokální build.  
8. **Autocomplete:** Doplnit na formuláře.

---

## Závěr

Projekt **většinou dodržuje** .cursorrules: struktura, PHP, SQL, dokumentace a základní UI jsou v pořádku. Hlavní mezery jsou:

- **Footer** (copyright Sensio.cz),
- **Skrývatelný úvod na homepage** (wizard + případně login),
- **Light/Dark mode**,
- **Prohlášení o přístupnosti** a **klávesové zkratky** (včetně jejich uvedení v prohlášení).

Po doplnění těchto bodů bude soulad s předpisem výrazně vyšší.

**Celkové hodnocení:** cca 7,5/10 – solidní základ, několik cílených úprav dovede projekt plně do souladu s .cursorrules.
