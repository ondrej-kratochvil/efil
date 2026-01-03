<?php
/**
 * Email Helper Functions
 * Handles sending emails via SMTP
 */

/**
 * Send email via SMTP
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $htmlBody HTML body of email
 * @param array $config SMTP configuration
 * @return bool Success status
 */
function sendEmail($to, $subject, $htmlBody, $config) {
    // For now, use PHP mail() function as fallback
    // In production, use PHPMailer or similar library for proper SMTP
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
        'Reply-To: ' . $config['from_email'],
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $success = mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    
    // Log email for debugging
    if (!$success) {
        error_log("Failed to send email to: $to, subject: $subject");
    }
    
    return $success;
}

/**
 * Get email template wrapper
 * 
 * @param string $content Email content HTML
 * @return string Complete HTML email
 */
function getEmailTemplate($content) {
    return '
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #1e293b; margin: 0; padding: 0; background-color: #f8fafc; }
        .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
        .content { padding: 30px 20px; }
        .button { display: inline-block; background: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 20px 0; }
        .footer { background: #f1f5f9; padding: 20px; text-align: center; font-size: 14px; color: #64748b; }
        .footer a { color: #4f46e5; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⬡ eFil</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Evidence Filamentů</p>
        </div>
        <div class="content">
            ' . $content . '
        </div>
        <div class="footer">
            <p>Tento email byl odeslán systémem <strong>eFil</strong></p>
            <p>Provozuje <a href="https://sensio.cz">Sensio.cz s.r.o.</a></p>
            <p style="font-size: 12px; margin-top: 10px;">Máte otázky? <a href="mailto:podpora@efil.cz">Kontaktujte podporu</a></p>
        </div>
    </div>
</body>
</html>';
}

/**
 * Send password reset email
 * 
 * @param string $to Recipient email
 * @param string $resetUrl URL with reset token
 * @param array $config SMTP config
 * @return bool Success status
 */
function sendPasswordResetEmail($to, $resetUrl, $config) {
    $content = '
        <h2>Obnova hesla</h2>
        <p>Obdrželi jsme požadavek na obnovení hesla k vašemu účtu v aplikaci eFil.</p>
        <p>Pro nastavení nového hesla klikněte na tlačítko níže:</p>
        <p style="text-align: center;">
            <a href="' . htmlspecialchars($resetUrl) . '" class="button">Obnovit heslo</a>
        </p>
        <p style="color: #64748b; font-size: 14px;">Odkaz je platný po dobu 1 hodiny.</p>
        <p style="color: #64748b; font-size: 14px;">Pokud jste o obnovu hesla nežádali, tento email ignorujte.</p>
    ';
    
    return sendEmail($to, 'Obnova hesla - eFil', getEmailTemplate($content), $config);
}

/**
 * Send new user invitation email (user exists)
 * 
 * @param string $to Recipient email
 * @param string $inventoryName Name of inventory they were added to
 * @param string $loginUrl Login URL
 * @param array $config SMTP config
 * @return bool Success status
 */
function sendInventoryInvitationEmail($to, $inventoryName, $loginUrl, $config) {
    $content = '
        <h2>Pozvánka do evidence</h2>
        <p>Dobrá zpráva! Byli jste přidáni do evidence <strong>' . htmlspecialchars($inventoryName) . '</strong>.</p>
        <p>Evidence je nyní dostupná ve vašem účtu eFil.</p>
        <p style="text-align: center;">
            <a href="' . htmlspecialchars($loginUrl) . '" class="button">Přihlásit se do eFil</a>
        </p>
        <p style="color: #64748b; font-size: 14px;">Po přihlášení můžete přepínat mezi vašimi evidencemi v menu aplikace.</p>
    ';
    
    return sendEmail($to, 'Pozvánka do evidence - eFil', getEmailTemplate($content), $config);
}

/**
 * Send new account creation email (user doesn't exist)
 * 
 * @param string $to Recipient email
 * @param string $inventoryName Name of inventory they were added to
 * @param string $setPasswordUrl URL to set password
 * @param array $config SMTP config
 * @return bool Success status
 */
function sendNewAccountEmail($to, $inventoryName, $setPasswordUrl, $config) {
    $content = '
        <h2>Vítejte v eFil!</h2>
        <p>Pro vás byl vytvořen účet v aplikaci <strong>eFil - Evidence Filamentů</strong>.</p>
        <p>Byli jste přidáni do evidence: <strong>' . htmlspecialchars($inventoryName) . '</strong></p>
        <p>Pro dokončení registrace klikněte na tlačítko níže a nastavte si heslo:</p>
        <p style="text-align: center;">
            <a href="' . htmlspecialchars($setPasswordUrl) . '" class="button">Nastavit heslo</a>
        </p>
        <p style="color: #64748b; font-size: 14px;">Odkaz je platný po dobu 24 hodin.</p>
        <p style="color: #64748b; font-size: 14px;">Po nastavení hesla se můžete přihlásit s emailem: <strong>' . htmlspecialchars($to) . '</strong></p>
    ';
    
    return sendEmail($to, 'Váš nový účet v eFil', getEmailTemplate($content), $config);
}

/**
 * Send role change notification email
 * 
 * @param string $to Recipient email
 * @param string $inventoryName Name of inventory
 * @param string $newRole New role (read/write/manage)
 * @param array $config SMTP config
 * @return bool Success status
 */
function sendRoleChangeEmail($to, $inventoryName, $newRole, $config) {
    $roleNames = [
        'read' => 'Jen čtení',
        'write' => 'Zápis',
        'manage' => 'Správa'
    ];
    
    $content = '
        <h2>Změna oprávnění</h2>
        <p>Vaše oprávnění v evidenci <strong>' . htmlspecialchars($inventoryName) . '</strong> byla změněna.</p>
        <p>Nová úroveň přístupu: <strong>' . ($roleNames[$newRole] ?? $newRole) . '</strong></p>
        <p style="color: #64748b; font-size: 14px;">
            ' . ($newRole === 'read' ? 'Můžete prohlížet data, ale nemůžete je upravovat.' : 
                ($newRole === 'write' ? 'Můžete přidávat a upravovat filamenty a zapisovat čerpání.' : 
                'Máte plný přístup včetně správy uživatelů.')) . '
        </p>
    ';
    
    return sendEmail($to, 'Změna oprávnění - eFil', getEmailTemplate($content), $config);
}

/**
 * Send removal from inventory notification
 * 
 * @param string $to Recipient email
 * @param string $inventoryName Name of inventory
 * @param array $config SMTP config
 * @return bool Success status
 */
function sendRemovalEmail($to, $inventoryName, $config) {
    $content = '
        <h2>Odebrání z evidence</h2>
        <p>Byli jste odebráni z evidence <strong>' . htmlspecialchars($inventoryName) . '</strong>.</p>
        <p>Již nemáte přístup k datům této evidence.</p>
        <p style="color: #64748b; font-size: 14px;">Pokud si myslíte, že došlo k chybě, kontaktujte správce evidence.</p>
    ';
    
    return sendEmail($to, 'Odebrání z evidence - eFil', getEmailTemplate($content), $config);
}
