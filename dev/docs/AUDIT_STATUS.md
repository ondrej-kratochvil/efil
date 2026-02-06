# Status oprav z auditu

**Poslední audit:** 2026-02-04  
**Detailní zpráva:** [AUDIT_REPORT.md](./AUDIT_REPORT.md)

---

## ✅ Dokončené opravy (z předchozích auditů)

1. **declare(strict_types=1)** – všechny API soubory + config.php
2. **PDO** – prepared statements v API (2× query() v helperech viz AUDIT_REPORT – nízká priorita)
3. **Favicon** – assets/img/favicon.svg, dynamický base path
4. **Footer** – © [rok] Sensio.cz s.r.o. s odkazem na https://sensio.cz
5. **Light/Dark mode** – prefers-color-scheme, přepínač v menu, localStorage (efil-theme)
6. **Prohlášení o přístupnosti** – sekce v Nápovědě, odkaz v footeru
7. **Klávesové zkratky** – F1 = Nápověda, Escape = zavření menu; uvedeny v Nápovědě a prohlášení
8. **Menu** – Evidence / Nastavení ve stromové struktuře (details)
9. **ES moduly** – app.js, router, api, state, config, utils, views/*

---

## ✅ Vyřešené (2026-02-04)

| Bod | Řešení |
|-----|--------|
| Úvod na přihlašovací stránce | Desktop vždy vidět; mobil po 1. přihlášení skrýt + tlačítko Zobrazit/Skrýt úvod, localStorage. Pravidlo zapsáno do .cursorrules. |
| i18n | Připraveno: assets/i18n/cs.json, en.json, i18n.js (t, setLang, init, applyTranslations), přepínač v menu a na auth, localStorage. |
| PDO query() | V api/helpers/spool_types.php a manufacturers.php nahrazeno query() za prepare/execute. |
| Klávesové zkratky | F1, Ctrl+N, Ctrl+S, Ctrl+E (přepnutí evidence), Escape; uvedeny v Nápovědě a prohlášení o přístupnosti. |
| Mapa webu | Sekce „Mapa webu“ v Nápovědě s odkazy na Evidence, Statistiky, Přepnutí evidence, Účet, Uživatelé, Cívky, Výrobce, Nápověda. |
| consumption_history_test | SELECT * nahrazen výčtem sloupců. |

---

## Shrnutí

Většina požadavků z .cursorrules je splněna. Aktuální audit viz **dev/docs/AUDIT_REPORT.md**.
