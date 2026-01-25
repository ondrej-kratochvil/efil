# Nastavení odesílání e-mailů

Aplikace eFil podporuje odesílání e-mailů přes SMTP pomocí knihovny PHPMailer. Pokud PHPMailer není nainstalován nebo nejsou nastaveny SMTP údaje, aplikace automaticky použije PHP `mail()` funkci jako fallback.

## Instalace PHPMailer

### Možnost 1: Stažení souborů (bez Composeru)

1. Stáhněte PHPMailer z https://github.com/PHPMailer/PHPMailer/releases
2. Rozbalte ZIP soubor
3. Zkopírujte obsah složky `src/` do `api/vendor/PHPMailer/src/`
4. Struktura by měla vypadat takto:
   ```
   api/
     vendor/
       PHPMailer/
         src/
           PHPMailer.php
           SMTP.php
           Exception.php
   ```

### Možnost 2: Pomocí Composeru (pokud máte Composer nainstalován)

```bash
composer require phpmailer/phpmailer
```

Composer soubory budou ve složce `vendor/` v kořenovém adresáři projektu.

## Konfigurace SMTP v .env

Přidejte následující proměnné do souboru `.env`:

```env
# SMTP Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=vas-email@gmail.com
SMTP_PASSWORD=vase-heslo-nebo-app-password
SMTP_FROM_EMAIL=noreply@efil.cz
SMTP_FROM_NAME=eFil - Evidence Filamentů
```

### Konfigurace pro Gmail

1. Použijte **App Password** místo běžného hesla:
   - Přejděte na https://myaccount.google.com/apppasswords
   - Vytvořte nové App Password pro "Mail"
   - Použijte vygenerované 16-místné heslo

2. Nastavte v `.env`:
   ```env
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USERNAME=vas-email@gmail.com
   SMTP_PASSWORD=xxxx xxxx xxxx xxxx  # App Password (bez mezer)
   ```

### Konfigurace pro jiné poskytovatele

#### Outlook/Hotmail
```env
SMTP_HOST=smtp-mail.outlook.com
SMTP_PORT=587
```

#### SendGrid
```env
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USERNAME=apikey
SMTP_PASSWORD=your-sendgrid-api-key
```

#### Seznam.cz
```env
SMTP_HOST=smtp.seznam.cz
SMTP_PORT=465
SMTP_USERNAME=vas-email@seznam.cz
SMTP_PASSWORD=vase-heslo
```

#### Sensio.cz hosting
Kontaktujte podporu hostingu pro SMTP údaje. Obvykle:
```env
SMTP_HOST=mail.sensio.cz
SMTP_PORT=587
SMTP_USERNAME=vas-email@sensio.cz
SMTP_PASSWORD=vase-heslo
```

## Testování odesílání e-mailů

Vytvořte testovací skript `test_email.php`:

```php
<?php
require_once 'config.php';
require_once 'api/helpers/email.php';

$testEmail = 'test@example.com'; // Změňte na vaši e-mailovou adresu

$result = sendEmail(
    $testEmail,
    'Test e-mailu z eFil',
    getEmailTemplate('<h2>Test e-mailu</h2><p>Pokud tento e-mail obdržíte, SMTP je správně nakonfigurován.</p>'),
    $smtpConfig
);

if ($result) {
    echo "✓ E-mail byl úspěšně odeslán na: $testEmail\n";
} else {
    echo "✗ Chyba při odesílání e-mailu. Zkontrolujte PHP error log.\n";
}
```

Spusťte:
```bash
php test_email.php
```

## Bezpečnost

- **Nikdy neukládejte SMTP hesla do Gitu** - soubor `.env` je v `.gitignore`
- **Použijte App Passwords** pro Gmail místo běžných hesel
- **V produkci odstraňte testovací skripty** (`test_email.php`)

## Odstraňování problémů

### E-maily se neodesílají

1. **Zkontrolujte PHP error log** - chyby se zapisují do error logu
2. **Ověřte SMTP údaje** - host, port, username, password
3. **Zkontrolujte firewall** - port 587 (STARTTLS) nebo 465 (SSL) musí být otevřený
4. **Zkontrolujte, zda je PHPMailer nainstalován** - pokud ne, použije se PHP `mail()`

### Chyba: "Class 'PHPMailer\PHPMailer\PHPMailer' not found"

PHPMailer není správně nainstalován. Zkontrolujte, že soubory jsou ve správné složce (`api/vendor/PHPMailer/src/`).

### E-maily se odesílají, ale nedorazí do schránky

- Zkontrolujte složku Spam/Nevyžádaná pošta
- Zkontrolujte, že adresa odesílatele (`SMTP_FROM_EMAIL`) je platná
- Někteří poskytovatelé blokují e-maily z nespolehlivých serverů

## Fallback na PHP mail()

Pokud PHPMailer není nainstalován nebo SMTP údaje nejsou nastaveny, aplikace automaticky použije PHP `mail()` funkci. Tato funkce však často nefunguje na produkčních serverech nebo vyžaduje speciální konfiguraci serveru.
