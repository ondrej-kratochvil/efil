# ⚙️ Doporučené nastavení PHP pro produkci

Tento dokument popisuje, jaká nastavení PHP byste měli změnit na hostingu pro bezpečné a správné fungování aplikace eFil.

## 🔴 POVINNÉ změny

### 1. `log_errors` → **On**
**Aktuální:** Off
**Změnit na:** On

**Důvod:**
- Umožní logování chyb do souboru
- Důležité pro debugging v produkci bez zobrazování chyb uživatelům
- Logy budou v adresáři `data/` (kontrolujte a promazávejte pravidelně!)

**Jak kontrolovat logy:**
- Přihlaste se přes FTP/SFTP
- Otevřete adresář `data/`
- Najděte soubor s PHP chybami (obvykle `error_log` nebo podobný)

### 2. `session.cookie_httponly` → **On**
**Aktuální:** Off
**Změnit na:** On

**Důvod:**
- Bezpečnostní opatření proti XSS (Cross-Site Scripting) útokům
- Session cookies nebudou přístupné přes JavaScript
- Zabraňuje krádeži session cookies škodlivým JavaScriptem

**⚠️ DŮLEŽITÉ:**
- Toto nastavení je kritické pro bezpečnost
- Bez něj jsou session cookies zranitelné vůči XSS útokům

### 3. `session.cookie_secure` → **On** (pouze pokud máte HTTPS)
**Aktuální:** Off
**Změnit na:** On (pouze pokud máte aktivní SSL certifikát!)

**Důvod:**
- Zajistí, že session cookies se přenášejí pouze přes HTTPS
- Zabraňuje odposlechu session cookies při přenosu přes nešifrované připojení

**⚠️ KRITICKÉ UPOZORNĚNÍ:**
- **Zapněte POUZE pokud máte aktivní SSL certifikát a HTTPS funguje!**
- Pokud zapnete bez HTTPS, sessions **NEBUDOU FUNGOVAT** a uživatelé se nebudou moci přihlásit!
- Ověřte, že vaše doména má aktivní HTTPS před zapnutím tohoto nastavení

**Jak ověřit HTTPS:**
- Otevřete `https://vas-domena.cz` v prohlížeči
- Zkontrolujte, že se zobrazí zelený zámek a žádné varování
- Pokud vidíte varování o neplatném certifikátu, NEPOUŽÍVEJTE `session.cookie_secure = On`

## ✅ Nastavení, která jsou v pořádku

Tyto hodnoty můžete ponechat na výchozích:

- **`display_errors: Off`** ✅ - Správně, chyby se nebudou zobrazovat uživatelům
- **`session.auto_start: Off`** ✅ - Správně, sessions se spouští manuálně
- **`session.use_only_cookies: On`** ✅ - Správně, sessions pouze přes cookies
- **`post_max_size: 64M`** ✅ - Dostatečné pro aplikaci
- **`upload_max_filesize: 60M`** ✅ - Dostatečné (aplikace neuploaduje soubory, ale pro případné budoucí rozšíření)
- **`memory_limit: 256M`** ✅ - Dostatečné pro aplikaci
- **`max_execution_time: 180`** ✅ - Dostatečné (3 minuty)
- **`max_input_vars: 1000`** ✅ - Dostatečné

## 📋 Shrnutí změn

**Pro server S HTTPS:**
```
log_errors: Off → On
session.cookie_httponly: Off → On
session.cookie_secure: Off → On
```

**Pro server BEZ HTTPS:**
```
log_errors: Off → On
session.cookie_httponly: Off → On
session.cookie_secure: Ponechat Off (nebo nechat Off)
```

## 🔍 Ověření nastavení

Po změně nastavení můžete ověřit, že fungují správně:

1. **Ověření log_errors:**
   - Vytvořte testovací PHP soubor s chybou
   - Zkontrolujte, že se chyba zapsala do logu v `data/`

2. **Ověření session nastavení:**
   - Přihlaste se do aplikace
   - Otevřete Developer Tools (F12) → Application → Cookies
   - Zkontrolujte, že session cookie má:
     - `HttpOnly` zaškrtnuté (pokud je `session.cookie_httponly = On`)
     - `Secure` zaškrtnuté (pokud je `session.cookie_secure = On` a máte HTTPS)

## 🐛 Řešení problémů

### Sessions nefungují po změně nastavení
- Zkontrolujte, že jste nezapnuli `session.cookie_secure` bez HTTPS
- Zkontrolujte, že máte aktivní SSL certifikát
- Zkontrolujte PHP error log pro detaily

### Chyby se nelogují
- Zkontrolujte, že `log_errors = On`
- Zkontrolujte oprávnění k adresáři `data/` (mělo by být zapisovatelné)
- Zkontrolujte, že webový server má oprávnění k zápisu do `data/`

## 📞 Podpora

Pokud máte problémy s nastavením PHP:
1. Zkontrolujte PHP error log v `data/`
2. Ověřte, že všechny změny byly uloženy
3. Zkontrolujte, že máte správná oprávnění
4. Kontaktujte podporu hostingu, pokud potřebujete změnit nastavení, které není v panelu

