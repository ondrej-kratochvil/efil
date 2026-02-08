# ✅ Deployment Checklist

Rychlý checklist pro nasazení na produkci.

## 📦 Příprava
- [ ] Všechny testy projdou lokálně
- [ ] Aplikace funguje lokálně
- [ ] Připraveny produkční DB přihlašovací údaje

## 📤 Nahrání souborů
- [ ] Nahrány všechny potřebné soubory (api/, assets/, database/, config.php, index.html)
- [ ] NENAHRAVÁNY: .env, tests/, .git/, PROJECT_BLUEPRINT.md

## 🔐 Konfigurace
- [ ] Vytvořen .env soubor na serveru s produkčními údaji
- [ ] Nastavena oprávnění .env (chmod 600)
- [ ] Vytvořena databáze na serveru
- [ ] Vytvořen DB uživatel s potřebnými oprávněními

## 🗄️ Databáze
- [ ] Spuštěn init_db.php (nebo migrační skripty)
- [ ] Databáze inicializována úspěšně
- [ ] SMAZÁN init_db.php ze serveru (nebo zabezpečen přes .htaccess)

## 🔒 Bezpečnost
- [ ] Nastavena oprávnění souborů (chmod 755 pro adresáře, 644 pro soubory)
- [ ] Vytvořen/zkontrolován .htaccess soubor
- [ ] Zakázán přístup k .env, tests/, migračním skriptům
- [ ] Upraven config.php pro produkci (error handling)

## ✅ Testování
- [ ] Aplikace se načítá v prohlížeči
- [ ] Přihlášení funguje (demo účet)
- [ ] API endpointy fungují
- [ ] Přidání filamentu funguje
- [ ] Úprava filamentu funguje
- [ ] Čerpání filamentu funguje

## 🧹 Úklid
- [ ] SMAZÁNY: init_db.php, update_schema.php, update_spool_schema.php (nebo zabezpečeny)
- [ ] SMAZÁN adresář tests/ (nebo zabezpečen)
- [ ] Vytvořen produkční admin účet (volitelné)

## 📝 Dokumentace
- [ ] Zaznamenány produkční přihlašovací údaje (bezpečně)
- [ ] Vytvořena záloha databáze
- [ ] Dokumentován postup pro budoucí aktualizace

---

**Důležité kontakty/údaje:**
- URL: ________________
- DB název: ________________
- DB uživatel: ________________
- Admin email: ________________

