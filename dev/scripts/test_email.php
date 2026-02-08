<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../api/helpers/email.php';

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