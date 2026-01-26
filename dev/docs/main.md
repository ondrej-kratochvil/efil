# eFil - Evidence Filamentů - Dokumentace

## 📋 Přehled projektu

**eFil** je profesionální webová aplikace pro komplexní správu 3D tiskových materiálů (filamentů). Aplikace poskytuje uživatelům přesný přehled o stavu zásob na základě reálného čerpání, nikoliv jen odhadů.

### Typ projektu
- **Full-stack aplikace** (PHP + Vanilla JavaScript)
- **Technický stack**: PHP 8.x, MySQL/MariaDB, Vanilla JS (ES6+), Tailwind CSS
- **Architektura**: REST API backend, SPA frontend s History API routováním

### Hlavní funkce
- Správa filamentů s detailními informacemi
- Sledování spotřeby s datem a možností vážení
- Historie čerpání s editací
- Statistiky a přehledy
- Multiuser funkce (sdílení evidencí, role)
- Knihovna cívek s vazbou na výrobce
- Chytré groupování a filtrování

---

## 📚 Struktura dokumentace

Tato dokumentace je modulární a obsahuje:

1. **[Architektura](./architektura.md)** - Technický popis architektury, databázové schéma, API struktura
2. **[Algoritmy](./algoritmy.md)** - Popis klíčových algoritmů (výpočet zůstatků, groupování, optgroups)
3. **[UI/UX](./ui.md)** - Dokumentace uživatelského rozhraní, navigace, komponenty
4. **[Manuální testy](./manual_tests.md)** - Scénáře pro manuální testování
5. **[Roadmapa](./roadmapa.md)** - Plánovaný vývoj a vylepšení

---

## 🚀 Rychlý start

### Požadavky
- PHP 8.0+
- MySQL/MariaDB 5.7+
- Webový server (Apache/Nginx) nebo WAMP/XAMPP

### Instalace
1. Naklonujte repozitář
2. Vytvořte `.env` soubor (viz `.env.example`)
3. Spusťte `php init_db.php` pro inicializaci databáze
4. Otevřete aplikaci v prohlížeči

### Demo účet
- **Email**: `demo@efil.cz`
- **Heslo**: `demo1234`

---

## 📁 Struktura projektu

```
efil-github/
├── api/                    # Backend API endpointy
│   ├── account/           # Správa účtu
│   ├── admin/             # Admin funkce
│   ├── auth/              # Autentizace
│   ├── consumption/       # Historie čerpání
│   ├── filaments/        # Správa filamentů
│   ├── helpers/           # Pomocné funkce (JWT, email)
│   ├── inventory/        # Správa evidencí
│   ├── spools/           # Knihovna cívek
│   └── users/            # Správa uživatelů
├── assets/
│   ├── css/              # Styly
│   └── js/               # Frontend JavaScript
├── database/
│   └── schema.sql        # Databázové schéma
├── dev/
│   ├── docs/             # Tato dokumentace
│   └── tests/            # Testovací skripty
├── config.php            # Konfigurace
├── init_db.php           # Inicializační skript
└── index.html            # Hlavní HTML soubor
```

---

## 🔑 Klíčové koncepty

### Evidence (Inventories)
Každý uživatel může vlastnit více evidencí (skladů). Evidence mohou být sdílené s dalšími uživateli s různými oprávněními (read/write/manage).

### Filamenty
Každý filament má:
- `user_display_id` - uživatelsky nastavitelné číslo (#1, #2, ...)
- `initial_weight_grams` - počáteční hmotnost
- Aktuální hmotnost se počítá: `initial_weight_grams + SUM(consumption_log.amount_grams)`

### Spotřeba (Consumption)
Záznamy spotřeby mohou být:
- **Záporné** (`amount_grams < 0`) - čerpání materiálu
- **Kladné** (`amount_grams > 0`) - korekce (např. přidání materiálu)

### Groupování
Filamenty se automaticky seskupují podle:
- Výrobce (manufacturer)
- Materiál (material)
- Barva (color_name)

Skupiny s více než jedním filamentem se zobrazují jako jedna položka s možností rozbalení.

---

## 🧪 Testování

Projekt obsahuje automatizované testy v `/tests/`:
- `balance_test.php` - Test výpočtu zůstatků
- `grouping_test.php` - Test groupování
- `multiuser_test.php` - Test multiuser funkcí
- `run_all_tests.php` - Spuštění všech testů

---

## 📝 Poznámky k vývoji

- Aplikace používá **Vanilla Stack** (bez frameworků)
- Backend: PHP 8.x s PDO prepared statements
- Frontend: ES6+ moduly, History API pro routování
- Bezpečnost: bcrypt pro hesla, session management, SQL injection ochrana
- Dokumentace: Modulární struktura v `/dev/docs/`

---

## 🔗 Související dokumenty

- [README.md](../../README.md) - Základní dokumentace projektu
- [DEPLOYMENT.md](../../DEPLOYMENT.md) - Návod na nasazení
- [CHANGELOG.md](../../CHANGELOG.md) - Seznam změn
- [EMAIL_SETUP.md](../../EMAIL_SETUP.md) - Nastavení e-mailů
