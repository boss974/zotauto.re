<?php
/**
 * config.php — Configuration & session sécurisée pour le portail admin ZOT AUTO.
 * Ne jamais afficher les erreurs PHP en production.
 */

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// --- Constantes -------------------------------------------------------
const WEBMASTER_EMAIL  = 'webmaster@zotauto.re';
// Expéditeur = une VRAIE boîte du domaine (LWS bloque l'envoi depuis une adresse locale inexistante).
const MAIL_FROM         = 'webmaster@zotauto.re';
const AUTH_FILE          = __DIR__ . '/.auth.php';
const OTP_TTL            = 600; // 10 minutes
const MAX_OTP_TRIES      = 5;
const MAX_LOGIN_TRIES    = 8;
const LOGIN_LOCK_SECONDS = 600; // 10 minutes de blocage après trop d'échecs
const ADMIN_SESSION_MAX_IDLE = 8 * 3600; // 8h d'inactivité

// --- Session sécurisée --------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');

    session_name('zotauto_admin_sess');
    session_start();
}

// --- CSRF ---------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check(): bool
{
    $sent = $_POST['csrf_token'] ?? '';
    $known = $_SESSION['csrf_token'] ?? '';
    if ($sent === '' || $known === '') {
        return false;
    }
    return hash_equals($known, $sent);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
